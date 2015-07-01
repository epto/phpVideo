<?
/*
 * PHPAvi Ver. 1.0 Beta
 *
 * Copyright (c) 2013 EPTO (A) <epto@tramaci.org>
 *
 * Some rights Reserved
 *
 * For the full copyright and license information, please view the LICENSE file.
 */
 
function AviChunk($junk) { return substr($junk['fourcc'].'    ',0,4).pack('V',strlen($junk['data'])).$junk['data']; }

function AviList($junks) {
		
	$data=substr($junks['fourcc'].'    ',0,4);
	
	foreach($junks['list'] as &$junk) {
		if (isset($junk['list'])) {
			$data.=AviList($junk);
			$junk=''; 
			} else {
			$data.=AviChunk($junk);
			$junk='';
			}		
		}
		
	$data='LIST'.pack('V',strlen($data)).$data;
	return $data;
	}

function AviC_Chunk($fourcc,$data) { return array('fourcc' => $fourcc, 'data' => $data); }

function AviC_avih($width,$height,$fps,$streams,$totframes,$mabmav=1048576) {
	    $data = pack('V*', 
	    	intval(1000000/$fps),
	    	$mabmav,
	    	0,
	    	0x110,
	    	$totframes,
	    	0,
	    	$streams,
	    	0,
	    	$width,
	    	$height,
	    	0,0,0,0)
	    	;
	    	
	return array(
		'fourcc'	=>	'avih'	,
		'data'		=>	$data	)
		;
	}

function AviC_List($fourcc) {
	return array(
		'fourcc'	=>	$fourcc	,
		'list'		=>	array()	)
		;
	}

function AviC_Junk($fourcc,$data) {
	 return array(
		'fourcc'	=>	$fourcc	,
		'data'		=>	$data	)
		;
	}

function AviC_LAdd(&$list,$junk) { $list['list'][] = $junk; }
		
function AviC_strh_Video($width,$height,$fps,$totframes,$mabMav=1048576) {
	 $strh = AviC_List('strl');
	 
	    $data='vidsMJPG'.
	    	pack('V',0).
	    	pack('v',0).
	    	pack('v',0).
	    	pack('V*',
	    		0,
	    		1,
	    		$fps,
	    		0,
	    		$totframes,
	    		$mabMav,
	    		0xFFFFFFFF,
	    		0).
	    	pack('v*',0,0,$width,$height);
	
	  AviC_LAdd($strh, AviC_Junk('strh',$data));
	  
	     $data=pack('V*',
	     	40,
	     	$width,
	     	$height).
	     	pack('v',1).
	     	pack('v',24).
	     	'MJPG'.
	     	pack('V',$width*$height*3).
	     	pack('V*',0,0,0,0)
		;
	   
	   AviC_LAdd($strh, AviC_Junk('strf',$data));
	   AviC_LAdd($strh, AviC_Junk('JUNK',str_pad('',4120,"\0")));
	   
	return $strh;	    		
	}

function AviC_strh_Audio($sRate,$totbytes) {
	 $strh = AviC_List('strl');
	 
	    $data="auds\0\0\0\0".
	    	pack('V',0).
	    	pack('v',0).
	    	pack('v',0).
	    	pack('V*',
	    		1,
	    		2,
	    		$sRate*2,
	    		0,
	    		$totbytes,
	    		$sRate*2,
	    		0xFFFFFFFF,
	    		2).
	    	pack('v*',0,0,0,0)
		;
	
	  AviC_LAdd($strh, AviC_Junk('strh',$data));
	  
	     $data=pack('v*',1,1).
	           pack('V*',$sRate,$sRate*2).
	           pack('v*',2,16,0)
		   ;
	   
	   AviC_LAdd($strh, AviC_Junk('strf',$data));
	   AviC_LAdd($strh, AviC_Junk('JUNK',str_pad('',4120,"\0")));
	   
	return $strh;	    		
	}

function AviC_Odml($totframes) {
	 $odml =  AviC_List('odml');
	 
		$data = pack('V',$totframes).
			str_pad('',244,"\0")
			;
		
	AviC_LAdd($odml, AviC_Junk('dmlh',$data));	
	return $odml;
	}

function AviC_hdrl($width,$height,$fps=1,$sRate=0,$totframes=0,$totSamples=0,$mabmav=1048576) {
        $hdrl =  AviC_List('hdrl');
	if ($sRate!=0) $streams=2; else $streams=1;
	
	$junk = AviC_avih($width,$height,$fps,$streams,$totframes,$mabmav);
	AviC_LAdd($hdrl, $junk);
	
	$junk = AviC_strh_Video($width,$height,$fps,$totframes,$mabmav);
	AviC_LAdd($hdrl, $junk);
	if ($streams==2) {
	        $junk = AviC_strh_Audio($sRate,$totSamples);
	        AviC_LAdd($hdrl, $junk);
		}
	
	$junk = AviC_Odml($totframes);
	AviC_LAdd($hdrl, $junk);
	
	return AviList($hdrl);
	}
 
function AviVideoFrame(&$H,&$jpg) {
	$i  = ftell($H['h']);
	$sz = strlen($jpg);
	fwrite($H['h'],'00dc'.pack('V',$sz).$jpg);
	$H['fra']++;
	$H['idx'][] = array(
		't'	=>	'00dc'		,
		'i'	=>	$i-$H['movi']-8 ,
		's'	=>	$sz		)
		;
	if ($sz>$H['mav']) $H['mav']=$sz;
	}       
        
function AviAudioFrame(&$H,&$wav) {
	$i  = ftell($H['h']);
	$sz = strlen($wav);
	fwrite($H['h'],'01wb'.pack('V',$sz).$wav);
	$H['aby']+=$sz;
	$H['idx'][] = array(
		't'	=>	'01wb'		,
		'i'	=>	$i-$H['movi']-8 ,
		's'	=>	$sz		)
		;
	
	if ($sz>$H['mab']) $H['mab']=$sz;
	}        

$INVOTYPE = array(
	'title'		=> 'INAM',
	'encoded' 	=> 'IEDT',
	'subject'	=> 'ISBJ',
	'copyright'	=> 'ICOP',
	'keywords'	=> 'IKEY',
	'software'	=> 'ISFT',
	'source'	=> 'ISRF',
	'produced'	=> 'IPRO')
	;

function AviC_Info($iinfo) {
	global $INVOTYPE;
	
	$info =  AviC_List('INFO');
	foreach($INVOTYPE as $k => $v) {
		 if (isset($iinfo[$k])) {
		        $junk = AviC_Chunk($v,$iinfo[$k]);
	        	AviC_LAdd($info, $junk);
	        	}  
		}
	return $info;	
	}        

function AviNew($fo,$width,$height,$fps=1,$sRate=0) {
	$h=fopen($fo,'wb');
	if ($h===false) return false;
	fwrite($h,"RIFF\0\0\0\0AVI ");
	fwrite($h,str_pad('',10228,"\0"));
	fseek($h,0x2800,SEEK_SET);
	if ($sRate!=0) $apf=intval((2*$sRate)/$fps); else $apf=0;
	fwrite($h,"LIST\0\0\0\0movi");
	        
	$A = array(
		'h'	=>	$h	,
		'wh'	=>	$width	,
		'he'	=>	$height	,
		'fps'	=>	$fps	,
		'srt'	=>	$sRate	,
		'apf'	=>	$apf    ,
		'aby'	=>	0	,
		'fra'	=>	0	,
		'movi'	=>	0x2800	,
		'mab'	=>	0	,
		'mav'	=>	0	,
		'info'	=>	array()	,	
		'idx'	=>	array()	,
		'buf'	=>	''	)
		;
	return $A;	
	}   
        
function AviEnd(&$avi) {
        $mabmav=$avi['mab']+$avi['mav'];
        
        if (strlen($avi['buf'])) AviAudioFrame($avi,$avi['buf']);
	$avi['buf']=''; 
        
        $endip = ftell($avi['h']);
        
        foreach($avi['idx'] as $v) $data.=substr($v['t'].'    ',0,4).pack('V',16).pack('V',$v['i']).pack('V',$v['s']);
	fwrite($avi['h'],'idx1'.pack('V',strlen($data)).$data);
	fwrite($avi['h'],'JUNK'.pack('V',64).str_pad("\0PHPAvi V1.0",64,"\0"));
	
	$size = ftell($avi['h']);
	fseek($avi['h'],0,SEEK_SET);
	fwrite($avi['h'],'RIFF'.pack('V',$size-8).'AVI ');
	
	$junk = AviC_hdrl($avi['wh'],$avi['he'],$avi['fps'],$avi['srt'],$avi['fra'],$avi['aby'],$mabmav);
	fwrite($avi['h'],$junk);
	
	if (is_array($avi['info']) AND count($avi['info']) > 0) {
	        $junk = AviC_Info($avi['info']);
	        fwrite($avi['h'],AviList($junk));
		}
	
	$pox = $avi['movi'] - ftell($avi['h']) - 8;
	fwrite($avi['h'],'JUNK'.pack('V',$pox).str_pad('',$pox,"\0"));
	
	fseek($avi['h'],$avi['movi'],SEEK_SET);
	fwrite($avi['h'],'LIST'.pack('V', $endip-$avi['movi']-8).'movi');
	fclose($avi['h']);
	$avi=null;
	}    
?>
