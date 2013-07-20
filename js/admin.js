function view_bi(tg){
	$(tg).parent().find('table.stats_popup').show();
}

function do_filter(){
	var get_params={};
	var loc=window.location.href.toString();
	var qpos=loc.indexOf('?');
	if (qpos!=-1) {
		loc=loc.substr(qpos+1);
		var matches=loc.match(/(by_[a-z]+)=([0-9]+)/gi);
		if (matches!=null && matches.length) {
			var imatch=[];
			for (i in matches) {
				imatch=matches[i].split('=');
				if (imatch.length==2) {
					get_params[imatch[0]]=imatch[1];
				}
			}
		}
	}
	var new_params='./?';
	var params_changed=false;
	$('#filters').find('select.filter, input.filter').each(
		function () {
			var ipar=$(this).val();
			if (ipar) {
				new_params+=this.id+'='+ipar+'&';
			}
			if (get_params[this.id]!=ipar) {
				params_changed=true;
			}
		}
	);
	if (params_changed) {
		window.location=new_params.replace(/\&$/gi, '');
	}
}

function on_filter_click () {
	var field_prefixes = ['year', 'month', 'day'];
	var dates = ['from', 'to'];
	for (i in dates) {
		var idate='';
		for (j in field_prefixes) {
			idate+='-'+$('#by_date_'+dates[i]+"_"+field_prefixes[j]).val();
		}
		$('#by_date_'+dates[i]).val(idate.replace(/^\-/gi, ''));
	}
	do_filter();
}

function filter_by_username(e){
	if (e.type=="keyup"){
		if (e.keyCode==13) {
			do_filter();
		}
	} else {
		do_filter();
	};
}

function update_date_filters() {
	var date_from=$('#by_date_from').val();
	var date_to=$('#by_date_to').val();
	if (date_from) {
		set_date_filter('from', date_from.split('-'));
	}

	if (date_to) {
		set_date_filter('to', date_to.split('-'));
	}
}

function set_date_filter (target, values) {
	var field_prefixes = ['year', 'month', 'day'];
	var selector;
	var key=0;
	for (i in field_prefixes) {
		selector='#by_date_'+target+'_'+field_prefixes[i];
		$(selector+' option[selected=selected]').removeAttr('selected');
		$(selector+' option[value='+values[key]+']').attr('selected', 'selected');
		key++;
	}
}

//var allow_hide_popup=false;

function lock_ip(tgt) {
	var tgt=$(tgt);
	$.get(
		base_url+'admin/lock_ip/'+tgt.attr('title'),
		function () {
			tgt.parent().html('Locked!');
		},
		'json'
	);
}

function unlock_ip(tgt) {
	var tgt=$(tgt);
	$.get(
		base_url+'admin/unlock_ip/'+tgt.attr('title'),
		function () {
			tgt.parent().html('Unlocked!');
		},
		'json'
	);
}

$(document).ready(
	function () {
		/*
		$('body').click(
			function () {
				if (allow_hide_popup) {
					$('table.stats_popup').hide();
				}
			}
		);

		$('a.view_bi').mouseover(
			function (){
				allow_hide_popup=false;
			}
		);

		$('a.view_bi').mouseout(
			function (){
				allow_hide_popup=true;
			}
		);
		*/
		//activating filters
		$('#filters').find('select.filter[id^=by_date]').change(do_filter);
		$('#filter_by_date').click(on_filter_click);
		$('#by_username').blur(filter_by_username);
		$('#by_username').keyup(filter_by_username);
		update_date_filters();
	}
);
