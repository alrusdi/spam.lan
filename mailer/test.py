import os
import memcache
import simplejson
import hashlib

data = {
    'base':{
                'started':1295406362, 
                'threads_num':10, 
                'tasks_num':315
            }, 
    'tasks':{
             '1':{
                        'mails':316, 
                        'sent': 15, 
                        'fails':1, 
                        'started':1295406362+12000
                    }, 
             '3':{
                        'mails':17, 
                        'sent': 13, 
                        'fails':0, 
                        'started':1295406362+300
                    }, 
             }
}

jsoned = simplejson.dumps(data)

key = 'MAILING' + 'MONITOR' + 'INFO'
key = hashlib.md5(key).hexdigest()

mc = memcache.Client(['127.0.0.1:11211'], debug=0)
mc.set(key, jsoned,  1*60)
print key
