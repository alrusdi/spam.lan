import os
import sys
import hashlib
import smtplib
import mimetypes
from email.MIMEMultipart import MIMEMultipart
from email.MIMEBase import MIMEBase
from email.MIMEText import MIMEText
from email.Encoders import encode_base64

import thread
import threading
import MySQLdb
import MySQLdb.cursors
import re
import Queue
import time
import random
import re
import pprint

import subprocess
import json
import time

import memcache

MC_KEY = hashlib.md5('MAILING' + 'MONITOR' + 'INFO').hexdigest()
MC = memcache.Client(['127.0.0.1:11211'], debug=0)
def update_live_stats():
    jsoned = json.dumps(GLOBAL_STATS)
    MC.set(MC_KEY, jsoned,  2*60)

def close_live_stats():
    MC.delete(MC_KEY)

def setUserAsFailedForMailing(params):
    if (int(DB_CONFIG['nats']['version'])==3):
        if (params['users_category']=='members'):
            query='UPDATE members SET mailok=3 WHERE memberid='+str(params['memberid'])
            ncursor.execute(query)
        return True
    else:
        return True

def sendMail(params):
    msg = MIMEMultipart()
    msg['From'] =params['from']
    msg['To'] = params['to']
    msg['Subject'] = params['subject']
    msg.attach(MIMEText(params['text'].decode('unicode_escape'),  'html'))
    try:
        mailServer = smtplib.SMTP(params['smtp']['server'], int(params['smtp']['port']))
        mailServer.ehlo()
        mailServer.esmtp_features["auth"] = "LOGIN PLAIN"
        mailServer.login(params['smtp']['user'], params['smtp']['password'])
        mailServer.sendmail(params['from'], params['to'], msg.as_string())
        mailServer.close()
        return True
    except:
        setUserAsFailedForMailing(params)
        prog = re.compile('[^0-9a-z\- ]', re.U|re.M)
        return prog.sub( '',  str(sys.exc_info()[1]) ) 

num_threads = 10

# LOGGING
#each thread is writing its own log to awoid race conditions
def write_log(thread_name,  log_data):
    if ( not(is_key_exists(log_data,  0)) ):
        return False;
    log_path=os.path.dirname(os.path.abspath( __file__ ))+'/temp_logs/'+thread_name+'.log'
    f = open(log_path,  'a+')
    f.write(json.dumps(log_data))
    f.close()

#joins logs of all threads and calculates number of failed and completed tasks 
def get_log(task_id,  total):
    cur_dir=os.path.dirname(os.path.abspath( __file__ ))+'/'
    proc = subprocess.Popen('cat '+cur_dir+'temp_logs/thread_'+task_id+'_*',
                            shell=True,
                            stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE,
                            stderr=subprocess.STDOUT,
                            )
    stdout_value, stderr_value = proc.communicate()
    err_string=stdout_value.rstrip(' ,\n').replace('"}][{"', '"},{"')
    try:
        errs = json.loads(err_string)
    except:
        errs={}
    if (is_key_exists(errs,  0) and is_key_exists(errs[0],  'error')):
        failed = len(errs)
    else:
        failed=0
        err_string=''
    ret={'complete':total-failed, 'failed':failed, 'log':err_string}
    return ret

#removing logs of all threads
def flush_log():
    cur_dir=os.path.dirname(os.path.abspath( __file__ ))+'/'
    proc = subprocess.Popen('rm -rf '+cur_dir+'temp_logs/thread_*',
                            shell=True,
                            stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE,
                            stderr=subprocess.STDOUT,
                            )
    stdout_value, stderr_value = proc.communicate()
#END LOGGING

GLOBAL_STATS = {
                'base':{'started':int(time.time())}, 
                'tasks':{}
                }

PREV_TIME = time.time()
def register_complete_mail(task_id,  state):
    global PREV_TIME
    task_id=str(task_id)
    lock = threading.Lock()
    lock.acquire()
    try:
        GLOBAL_STATS['base']['threads_num'] = threading.activeCount()-1
        GLOBAL_STATS['base']['tasks_num'] = q.qsize()
        if (state):
            GLOBAL_STATS['tasks'][task_id]['sent']+=1
        else:
            GLOBAL_STATS['tasks'][task_id]['fails']+=1
        #updating memcache records each 3rd second
        if (time.time() - PREV_TIME>3):
            update_live_stats()
            PREV_TIME = time.time()
    finally:
        lock.release()

#each thread getting and runing jobs here
def worker(myname):
        log_data=[]
        while True:
            #getting new job from queue
            try:
                item = q.get(False)
            except Queue.Empty:
                #all task are done so we are writing error log and finishing job
                write_log(myname,  log_data)
                return
            result = sendMail(item)
            #appending error (if any) info to log
            state = True
            if not(result==True):
                item['error'] = result.replace('\"',  '"').replace('"',  '\"')
                log_data.append(item)
                state=False
            time.sleep( random.randint(10, 15) )
            register_complete_mail(item['task_id'], state)
            q.task_done()

def is_key_exists(in_dict,  key):
    try:
        in_dict[key]
    except (KeyError, TypeError, IndexError):
        return False
    return True

def get_substring(source_str, from_str, from2_str, to_str):
    pos = source_str.index(from_str)
    adv_pos = len(from_str)
    if (from2_str):
        pos = source_str.index(from2_str, pos)+len(from2_str)
        adv_pos=0
    end_pos=source_str.index(to_str, pos+adv_pos)
    return source_str[pos+adv_pos:end_pos]

def parse_php_array_contents(ar):
    ret = {}
    ar = ar.split(',')
    for i in ar:
        istr=i.split('=>')
        key=get_substring(istr[0],  "'", False,   "'")
        val=get_substring(istr[1],  "'", False,   "'")
        ret[key] = val
    return ret

def get_php_db_config():
    cur_dir=os.path.dirname(os.path.abspath( __file__ ))+'/'
    config_file = cur_dir + PHP_CONFIG_FILE
    try:
        fhandle = open(config_file,  'r')
    except:
        print "ERROR! Can't open php config file"
        sys.exit()
    config_raw = fhandle.read()
    ret = {'nats':{},  'spam':{},  'nats_version':0}

    spam= get_substring(config_raw,  '$DB_SPAM',  '(',  ');')
    ret['spam'] = parse_php_array_contents(spam) 

    return ret

def get_statuses_config(config_file=False):
    if (config_file==False):
        cur_dir = os.path.dirname(os.path.abspath( __file__ ))+'/'
        config_file = cur_dir+'../configs/status.ini'
    try:
        fh = open(config_file , 'r')
        data = fh.read()
        fh.close()
    except:
        print('ERROR! Can\'t read the status.ini')
        return False
    data = data.split('\n')
    ret = {
            '3':{'members':{},  'webmasters':{}}, 
            '4':{'members':{},  'webmasters':{}}
            }
    for i in data:
        ival=i.strip()
        if (ival=='' or ival[0:1]=='#'):
            continue
        ival=ival.split('=')
        key=ival[0].strip()
        val=ival[1].strip()
        if (key == 'NATS_VERSION'):
            cur_version=val
        elif (key == 'CATEGORY'):
            cur_category=val
        else:
            ret[cur_version][cur_category][key]=val
    return ret

STATUSES = get_statuses_config()
#replaces markers like %USERNAME% with proper values from member data
def expand_tpl_params(itpl,  imember):
    for key in TPL_REPLACEMENTS.iterkeys():
        val = TPL_REPLACEMENTS[key]
        if (is_key_exists(imember,  val)):
            if (val=='status'):
                user_status = str(imember['status'])
                try:
                    ver = DB_CONFIG['nats']['version']
                    cat = imember['users_category']
                    replace_with = STATUSES[ver][cat][user_status]
                except:
                    continue
            else:
                replace_with = str(imember[val])
            itpl['name'] = itpl['name'].replace(str(key),  replace_with)
            itpl['contents'] = itpl['contents'].replace(str(key),  replace_with)
    return itpl

def get_tasks(by_id=False,  limit=False):
        tasks={}
        if (by_id==False):
            #getting 'run once'-type tasks
            gmt = time.gmtime()
            end_of_this_day = str(gmt[0])+'-'+str(gmt[1]).rjust(2, '0')+'-'+str(gmt[2]).rjust(2, '0')+' 00:00:00'
            cursor.execute("SELECT * FROM tasks WHERE type='once' AND when_to_start<='"+end_of_this_day+"' AND is_disabled=0 AND status='NEW'")
            res = cursor.fetchall()

            #getting 'periodical'-type tasks
            cursor.execute("SELECT * FROM tasks \
                                WHERE DATEDIFF(NOW(), last_run_time)>period \
                                AND is_disabled=0 AND type='periodicaly' \
                                AND status IN('NEW', 'CONTINUING') AND when_to_start<='"+end_of_this_day+"'")
            res =  res + cursor.fetchall()
        else:
            cursor.execute("SELECT * FROM tasks WHERE id="+str(by_id))
            res =  cursor.fetchall()

        templates = {}
        for i in res:
            tid = str(i['template_id'])
            templates[tid] = tid
        if len(templates)<1:
            return tasks
        cursor.execute("SELECT * FROM templates WHERE id IN ("+','.join(templates)+") AND is_disabled=0")
        templates_raw = cursor.fetchall()
        templates = {}
        for i in templates_raw:
            templates[i['id']] = i

        #generating tasks
        for i in res:
            i['params'] = json.loads(i['params_json'])
            needs_limit=False
            if is_key_exists(templates, i['template_id']):
                    tpl = templates[i['template_id']]
                    if i['params']['user_types'] == '': #ie users given in individual order
                        uids = ','.join(i['params']['uids'])
                        utype = i['params']['uids_type']
                        if (utype=='members'):
                            needs_limit=True
                            if (int(DB_CONFIG['nats']['version'])==3):
                                query = 'SELECT *, "members" AS users_category FROM members WHERE memberid IN ('+uids+') AND email<>\'\''
                            else:
                                query = 'SELECT m.*, mi.firstname, mi.lastname, "members" AS users_category FROM member AS m, member_info as mi WHERE m.memberid IN ('+uids+')'
                        else:
                            if (int(DB_CONFIG['nats']['version'])==3):
                                query = 'SELECT loginid AS memberid, username, email, active AS status, "webmasters" AS users_category FROM accounts WHERE loginid IN ('+uids+') AND email<>\'\''
                            else:
                                query = 'SELECT l.loginid AS memberid, l.username, l.status, ld.email, "webmasters" AS users_category FROM login AS l, login_detail AS ld WHERE l.loginid=ld.loginid AND l.loginid IN ('+uids+')'
                    else:
                        if i['params']['user_types']=='members':
                            needs_limit=True
                            if (int(DB_CONFIG['nats']['version'])==3):
                                #NOTE! Currently mailok=0 is for user enabled for receving spam, mailok=1 unused, mailok=2 for unsubscribed and finally mailok=3 for users with error
                                query = 'SELECT members.*, "members" AS users_category FROM members WHERE status IN ('+','.join(i['params']['member_statuses'])+') AND email<>\'\' AND mailok=0 GROUP BY email'
                            else:
                                query = 'SELECT m.*, mi.firstname, mi.lastname, "members" AS users_category FROM member AS m, member_info as mi WHERE m.memberid=mi.memberid AND m.mailok=0 AND m.status IN ('+','.join(i['params']['member_statuses'])+')'
                        else:
                            if (int(DB_CONFIG['nats']['version'])==3):
                                #NOTE! Currently mailok=0 is for user enabled for receving spam, mailok=1 unused, mailok=2 for unsubscribed and finally mailok=3 for users with error
                                query = 'SELECT loginid AS memberid, username, email, active AS status, firstname, lastname, "webmasters" AS users_category FROM accounts WHERE active=1 AND nomails=0 AND email<>\'\''
                            else:
                                query = 'SELECT l.loginid AS memberid, l.username, l.status, ld.email, ld.firstname, ld.lastname, "webmasters" AS users_category  FROM login AS l, login_detail AS ld WHERE l.loginid=ld.loginid AND ld.mailok=1'

                    if (needs_limit and limit==False):
                        limit={
                               'from':0, 
                               'count':GLOBAL_LIMIT
                               }
                    if ( not(limit==False) ):
                        query+=' LIMIT '+str(limit['from'])+','+str(limit['count'])
                    ncursor.execute(query)
                    data = ncursor.fetchall()
                    for member in data:
                        ext_tpl=expand_tpl_params(tpl.copy(),  member)
                        iid=str(i['id'])
                        fr = ext_tpl['email_from']
                        if (ext_tpl['sender_name']!=''):
                            fr = '"'+ext_tpl['sender_name']+'" <'+fr+'>'

                        itask={
                                    'from':fr, 
                                    'to':member['email'], 
                                    'subject':ext_tpl['name'], 
                                    'text':ext_tpl['contents'],
                                    'smtp':random.choice(SETTINGS['smtp']['servers']), 
                                    'type':i['type'], 
                                    'task_id':iid,
                                    'users_category':member['users_category'],
                                    'memberid':member['memberid'], 
                                    'limited':needs_limit
                               }
                        if (by_id==False):
                            if ( not( is_key_exists(tasks,  iid) ) ):
                                tasks[iid] = {}
                            tasks[iid][str(member['memberid'])]=itask
                        else:
                            tasks[str(member['memberid'])]=itask
        return tasks

#checking if this process still runing
proc = subprocess.Popen('ps aux | grep nats_mailer.py',
                        shell=True,
                        stdin=subprocess.PIPE,
                        stdout=subprocess.PIPE,
                        stderr=subprocess.STDOUT,
                        )
stdout_value, stderr_value = proc.communicate()

process_list = stdout_value.split("\n")

for i in process_list:
    if ( i!='' and not('grep' in i) and not(str(os.getpid()) in i) and not('eric4.py' in i) ):
        print('ERROR! Programm still runing ('+i+')')
        sys.exit()


PHP_CONFIG_FILE='../configs/config.php'
DB_CONFIG = get_php_db_config()

#loading smtp and mailing settings
conn = MySQLdb.connect (host = DB_CONFIG['spam']['host'],
                                        user = DB_CONFIG['spam']['user'],
                                        passwd = DB_CONFIG['spam']['pass'],
                                        db = DB_CONFIG['spam']['name'], 
                                        cursorclass=MySQLdb.cursors.DictCursor)
cursor = conn.cursor ()

#base settings for all smtp servers
cursor.execute ("SELECT * FROM settings_email LIMIT 1")
smtp_conf = cursor.fetchone()

if ( not(is_key_exists(smtp_conf, 'id'))):
    print('ERROR: smtp settings is empty')
    sys.exit()
SETTINGS={}
SETTINGS['smtp'] = smtp_conf
#servers itself
cursor.execute ("SELECT * FROM settings_email_servers WHERE settings_email_id="+str(smtp_conf['id']))
servers = cursor.fetchall()
SETTINGS['smtp'] ['servers']=servers

TPL_REPLACEMENTS={'%USERNAME%':'username',  '%USERMAIL%':'email',  '%USERID%':'memberid',  '%FIRSTNAME%':'firstname',  '%LASTNAME%':'lastname',  '%STATUS%':'status'}

cursor.execute("SELECT * FROM settings_db_nats WHERE is_active=1 LIMIT 1")
DB_CONFIG['nats'] = cursor.fetchone()

if ( not(is_key_exists(DB_CONFIG['nats'], 'id'))):
    print('ERROR: can\'t load active NATS DB settings')
    sys.exit()

nconn = MySQLdb.connect (
                                host = DB_CONFIG['nats']['server'],
                                user = DB_CONFIG['nats']['user'],
                                passwd = DB_CONFIG['nats']['password'],
                                db = DB_CONFIG['nats']['name'], 
                                cursorclass=MySQLdb.cursors.DictCursor)
ncursor = nconn.cursor ()

GLOBAL_LIMIT=10000
tasks = get_tasks()
if (tasks==False or len(tasks)<1):
    sys.exit()

flush_log()


for idx,  tasks_group in tasks.items():
    GLOBAL_STATS['tasks'][str(idx)]={
                                                   'mails':len(tasks_group), 
                                                    'sent': 0, 
                                                    'fails':0, 
                                                    'started':0
                                                   }


for idx, task_group in tasks.items():
    q = Queue.Queue(0)
    ilimit={
            'from':0, 
            'count':GLOBAL_LIMIT
            }

    gmt = time.gmtime()
    started = str(gmt[0])+'-'+str(gmt[1]).rjust(2, '0')+'-'+str(gmt[2]).rjust(2, '0')+' '+str(gmt[3])+':'+str(gmt[4])+':'+str(gmt[5])
    cursor.execute("UPDATE tasks SET status='RUNING', start_run_time='"+started+"' WHERE id="+str(idx)) 
    GLOBAL_STATS['tasks'][idx]['started']=int(time.time())
    total_tasks_count = 0
    while True:
        limited=False
        task_id=0
        current_tasks_count=len(task_group)
        total_tasks_count += current_tasks_count 
        for ktask, task in task_group.items():
            if (task['limited']):
                limited=True
                task_id=task['task_id']
            q.put(task)

        for i in range(num_threads):
            iargs = "thread_"+str(idx)+"_"+str(i)
            t = threading.Thread(target=worker,  args=(iargs,))
            t.daemon = True
            t.start()

        #waiting untill the threads finished their works
        while threading.activeCount()>1:
            time.sleep(1)

        if (limited and current_tasks_count>=GLOBAL_LIMIT):
            ilimit={
                    'from':ilimit['from']+ilimit['count'], 
                    'count':ilimit['count']
                    }
            task_group=get_tasks(by_id=task_id,  limit=ilimit)
            if ( len(task_group)<1 ):
                break
        else:
            break

    gmt = time.gmtime()
    finished = str(gmt[0])+'-'+str(gmt[1]).rjust(2, '0')+'-'+str(gmt[2]).rjust(2, '0')+' '+str(gmt[3])+':'+str(gmt[4])+':'+str(gmt[5])
    if (task['type']=='once'):
        status = 'COMPLETE'
    else:
        status = 'CONTINUING'
    log = get_log(str(idx),  total_tasks_count)
    cursor.execute("UPDATE tasks SET status='"+status+"', end_run_time='"+finished+"', last_run_time='"+finished+"' WHERE id="+str(idx))
    cursor.execute("INSERT INTO reports SET task_id='"+str(idx)+"', started='"+started+"', finished='"+finished+"', sends_complete='"+str(log['complete'])+"', sends_failed='"+str(log['failed'])+"', log=%s",  log['log'])
    #flush_log()

close_live_stats()

nconn.close()
ncursor.close()

cursor.close ()
conn.close()
