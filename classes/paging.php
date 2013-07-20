<?php
class paging {

	private $current_page;
	private $total_records;
	private $records_per_page;
	private $max_page_numbers_per_page;
	public  $page_number_identifier='%page_number%';
	public $display_prev=true;
	public $display_next=true;
	public $display_left_dots=true;
	public $display_right_dots=true;

	function __construct(
				$current_page=false,
				$total_records=false,
				$records_per_page=10,
				$max_page_numbers_per_page=10,
				$url_format=''
				)
	{
		$this->current_page=$current_page;
		$this->total_records=$total_records;
		$this->records_per_page=$records_per_page;
		$this->max_page_numbers_per_page=$max_page_numbers_per_page;
		$this->url_format=$url_format;
	}
	
	public function settings($settings) {
		foreach ($settings as $key=>$val) {
			$this->$key=$val;
		}
		return $this;
	}

	public function get_assoc_list(){
		$cp=$this->current_page;
		$tr=$this->total_records;
		$rpp=$this->records_per_page;
		$mppp=$this->max_page_numbers_per_page;
	
		if (!$tr || !$rpp || $tr<=$rpp){return $this->build_assoc_list(false);}

		$tp=(int)($tr/$rpp);//total pages
		if (($tp*$rpp)<$tr){$tp++;}
		if ($cp>$tp){ $cp=$tp;}
		if ($cp<1){ $cp=1;}

		$pp=0;//previous page
		$np=0;//next page
		if ($cp>1){$pp=$cp-1;}
		if ($cp<$tp){$np=$cp+1;}
		if ($tp<=$mppp){ return $this->build_assoc_list(1, $tp, $np, $pp);}

		$dl=0;//dots left
		$dr=0;//dots right
		$middle=(int)($mppp/2);
		if (($cp-$middle)>1){
			$from=$cp-$middle+1;;
		}else{
			$from=1;
			$to=$mppp;
		}
		if (($cp+$middle)<$tp){
			$to=$from+$mppp-1;
			$dr=$to+1;
			
		}else{
			$from=$tp-$mppp+1;
			$to=$tp;
		}
		if ($from>1){ $dl=$from-1;}

		return $this->build_assoc_list($from, $to, $np, $pp, $dl, $dr);
	}


	public function get_html(){
		$ar=$this->get_assoc_list();
		$ohtml='';
		if ($ar['prev']){ 
			$ohtml.='<span class="pg_prev"><a href="'.$this->page_to_url($ar['prev']).'"><<</a></span>';
		}
		if ($ar['dots_left']){ 
			$ohtml.='<span class="pg_ldots"><a href="'.$this->page_to_url($ar['dots_left']).'">...</a></span>';
		}
		$ctp=count($ar['pages']);
		if ($ctp){
			$ohtml.='<span class="pg_pages">';
			for ($i=0; $i<$ctp; $i++){
				$ohtml.='<a href="'.$this->page_to_url($ar['pages'][$i]).'">'.$ar['pages'][$i].'</a>';
			}
			$ohtml.='</span>';
		}
		if ($ar['dots_right']){ 
			$ohtml.='<span class="pg_ldots"><a href="'.$this->page_to_url($ar['dots_right']).'">...</a></span>';
		}
		if ($ar['next']){ 
			$ohtml.='<span class="pg_prev"><a href="'.$this->page_to_url($ar['next']).'">>></a></span>';
		}
		return $ohtml;

	}

	private function build_assoc_list($start_pagenum=false, $end_pagenum='', $next='', $prev='', $dots_left='', $dots_right=''){
		$al=Array('prev'=>'', 'dots_left'=>'', 'pages'=>Array(), 'dots_right'=>'', 'next'=>'');
		if ($start_pagenum===false){
			return $al;
		}
		for ($i=$start_pagenum; $i<=$end_pagenum; $i++){
			$al['pages'][]=$i;
		}
		if ($this->display_prev){
			if ($prev){ $al['prev']=$prev;}
		}
		if ($this->display_next){		
			if ($next){ $al['next']=$next;}
		}
		if ($this->display_left_dots){
			if ($dots_left){ $al['dots_left']=$dots_left;}
		}
		if ($this->display_right_dots){
			if ($dots_right){ $al['dots_right']=$dots_right;}
		}

		return $al;
	}

	private function page_to_url($pg){
		if ($this->url_format){
			$uf=$this->url_format;
			$url=str_replace($this->page_number_identifier, $pg, $uf);
			return $url;
		}else{
			return '?page='.$pg;
		}
	}
}
?> 
