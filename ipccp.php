<?php
/*
Plugin Name: IPCCP

Plugin URI: http://blog.vimagic.de/ipccp/

Description: The IP City Cluster Plugin generates city cluster maps (based on where from people access your blog).  Modify the options to your liking in the <a href="options-general.php?page=ipccp.php">IPCCP options</a> panel.

Version: 0.b5.8

Author: Thomas M. B&ouml;sel
Author URI: http://blog.vimagic.de/

Lisense : GPL(http://www.gnu.org/copyleft/gpl.html)
/*  Copyright 2006  Thomas M. Bosel  (email : tmb@vimagic.de, site : http://blog.vimagic.de)
**
**  This program is free software; you can redistribute it and/or modify
**  it under the terms of the GNU General Public License as published by
**  the Free Software Foundation; either version 2 of the License, or
**  (at your option) any later version.
**
**  This program is distributed in the hope that it will be useful,
**  but WITHOUT ANY WARRANTY; without even the implied warranty of
**  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
**  GNU General Public License for more details.
**
**  You should have received a copy of the GNU General Public License
**  along with this program; if not, write to the Free Software
**  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define("VERSION","v0.b5.8");
define("DEBUGGING","0");

//////////////////////////////////////////////////////////////
// LETS GET THAT ADMIN PANEL INSTALLED				 	 	//
//////////////////////////////////////////////////////////////
add_action('admin_menu', 'ipccp_options');
if((!empty($_GET['page']) && $_GET['page'] == "ipccp.php")) {
	add_action('admin_head', 'ipccp_header');
	add_action('admin_footer', 'ipccp_footer');
}

//////////////////////////////////////////////////////////////
// CRON CONTROLLED RECLUSTERING	IN USER SPECIFIED PERIODS	//
//////////////////////////////////////////////////////////////
class WpIPCCP {
	////////////////////
	// USER-VARIABLES //
	////////////////////
	var $user_table_name;
	var $user_table_key;
	var $user_add_my_place;
	var $user_my_place_long;
	var $user_my_place_lat;
	var $user_DS_MIN;
	var $user_DS_MAX;
	var $user_DS_STEPS;
	var $user_DS_LOW;
	var $user_DS_HIGH;
	var $user_Border_Dot_Outer;
	var $user_Color_Crosshair;
	var $user_Color_Dot_Outer;
	var $user_Color_Dot_InnerMax;
	var $user_Color_Dot_InnerMin;
	var $user_which_clustering;
	var $user_SCC;
	var $user_CDist;
	var $user_delay;
	var $user_time_limit;
	var $user_template_output;
	var $user_use_imagemap;
	var $user_show_small_picture;
	var $user_image_width_min;
	var $user_image_width_max;
	var $user_redraw_on_update;
	var $user_read_from_file;
	var $user_file_name;
	var $user_cron;
	var $user_filter_includes;
	var $user_draw_legend;
	var $user_memory_efficient;
	var $user_jpg_quality;
	var $user_cluster_steps;
	var $user_which_cluster_steps;

	//////////////////////
	// SAVED-VARIABLES	//
	//////////////////////
	var $imagemap_min;
	var $imagemap_max;
	var $last_run_time_start;
	var $last_run_time_finish;
	var $status;
	var $recluster;

	///////////////////////
	// UNSAFED-VARIABLES //
	///////////////////////
	var $plugin_path_unix;
	var $loadFile;
	var $saveFile_clustered_min;
	var $saveFile_clustered_max;
	var $LON0;
	var $LAT0;
	var $LON90;
	var $LAT60;
	var $factor_of_scaling_min;
	var $factor_of_scaling_max;
	var $pplps; 					//PPL PER SECTOR :)
	var $DEBUG;
	var $VERBOS;

	//////////////////////////////////////////////////////////////////////////////
	// PRELIMINARIES															//
	//////////////////////////////////////////////////////////////////////////////
	function WpIPCCP() {
		$this->user_table_name='wp_Counterize';
		$this->user_table_key='IP';
		$this->user_add_my_place	=   1;
		$this->user_my_place_long	=   9.9;
		$this->user_my_place_lat	=  48.5;
		$this->user_DS_MIN			=   8;
		$this->user_DS_MAX			=  20;
		$this->user_DS_STEPS		=   7;
		$this->user_DS_LOW			=  10;
		$this->user_DS_HIGH			= 300;
		$this->user_Border_Dot_Outer=   2;
		$this->user_Color_Crosshair		="333333";
		$this->user_Color_Dot_Outer		="330000"; 
		$this->user_Color_Dot_InnerMax	="FF0000";	
		$this->user_Color_Dot_InnerMin	="97014B";		
		$this->user_which_clustering=2;
		$this->user_SCC				=   1.15;
		$this->user_CDist			=  15;
		$this->user_delay			=   0;
		$this->user_time_limit		= 900;
		$this->user_template_output="<div align=\"center\"><img src=\"%IPCCP_MIN%\" %WIDTH_MIN% %IMAGEMAP% alt=\"%BLOGNAME% Cluster Map\" border=\"0\" /></div>";
		$this->user_use_imagemap=1;
		$this->user_show_small_picture= 0;
		$this->user_image_width_min	=1000;
		$this->user_image_width_max	=2000;
		$this->user_redraw_on_update=   0;
		$this->user_read_from_file	=   0;
		$this->user_file_name='/Library/Tenon/WebServer/Logs/VHOST.access.log';
		$this->user_cron		   =86400;
		$this->user_filter_includes=array('vimagic');
		$this->user_draw_legend    =	3;		// 0 = OFF, 1 = ON PREVIEW ONLY, 2 = ON ZOOM ONLY, 3 = ON
		$this->user_memory_efficient=	0;
		$this->user_jpg_quality		=  80;
		$this->user_cluster_steps	=array('10','25','50','100','250','500','1000','2500','5000');
		$this->user_which_cluster_steps=1;		// 0 = EQUIDISTANT, 1 = USER DEFINED

		$this->options= array (
			'user_table_name' 		=> 'string',
			'user_table_key' 		=> 'string',
			'user_add_my_place' 	=> 'bool',
			'user_my_place_long' 	=> 'string',
			'user_my_place_lat' 	=> 'string',
			'user_DS_MIN' 			=> 'int',
			'user_DS_MAX' 			=> 'int',
			'user_DS_STEPS'			=> 'int',
			'user_DS_LOW'			=> 'int',
			'user_DS_HIGH' 			=> 'int',
			'user_Border_Dot_Outer' => 'int',			
			'user_Color_Crosshair' 		=> 'string',
			'user_Color_Dot_Outer' 		=> 'string',
			'user_Color_Dot_InnerMax' 	=> 'string',
			'user_Color_Dot_InnerMin' 	=> 'string',
			'user_which_clustering' 	=> 'int',
			'user_SCC' 				=> 'string',
			'user_CDist' 			=> 'int',
			'user_delay' 			=> 'bool',			
			'user_time_limit' 		=> 'int',
			'user_template_output'	=> 'html',
			'user_use_imagemap' 	=> 'int',
			'imagemap_min'			=> 'html',
			'imagemap_max'			=> 'html',
			'user_show_small_picture'	=> 'bool',
			'user_image_width_min'	=> 'int',
			'user_image_width_max'	=> 'int',
			'user_redraw_on_update'	=> 'bool',
			'user_read_from_file'	=> 'bool',
			'user_file_name'		=> 'string',
			'user_cron'				=> 'int',
			'user_filter_includes'	=> 'array',
			'user_draw_legend'		=> 'int',
			'user_memory_efficient' => 'bool',
			'user_jpg_quality' 		=> 'int',
			'user_cluster_steps'	=> 'array',
			'user_which_cluster_steps' 	=> 'bool'
		);
		$this->imagemap_min='';
		$this->imagemap_max='';
		$this->last_run_time_start =	0;
		$this->last_run_time_finish=	0;
		$this->status='Whoohoo!  We got activated';
		$this->recluster=0;

		$this->plugin_path_URL = trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/ipccp';
		$this->plugin_path_unix = ABSPATH. 'wp-content/plugins/ipccp/';
		$this->loadFile = $this->plugin_path_unix."images/ipccp_in.jpg";	
		$this->saveFile_clustered_min = $this->plugin_path_unix.'images/ipccp_out_smal.jpg';	
		$this->saveFile_clustered_max = $this->plugin_path_unix.'images/ipccp_out_big.jpg';	
		$this->saveFile_clustered = $this->plugin_path_unix.'images/ipccp_out.jpg';	
		$this->LON0	=1000	-1;
		$this->LAT0	= 500	-1;
		$this->LON90=1500	+0;
		$this->LAT60= 170	-2;
		$this->factor_of_scaling_min=$this->user_image_width_min/2000.0;
		$this->factor_of_scaling_max=$this->user_image_width_max/2000.0;
		$this->DEBUG="DEBUGGING INFO: <br />";
		$this->VERBOSE=0;
		$this->pplps=($this->user_DS_HIGH-$this->user_DS_LOW)/($this->user_DS_STEPS);
		
		add_filter('the_content', array(&$this, '_filter'), 2);
		add_action('wp_head', array(&$this, '_wpHead'));
		//////////////////////////////////////////////////////////////
		// SCHEDULED RUN?									 	 	//
		//////////////////////////////////////////////////////////////
		if ((isset($_GET['activate']) && $_GET['activate'] != 'true')
				&& get_option('ipccp_last_run_time_start')!='' 
				&& time() > intval(get_option('ipccp_last_run_time_start'))+intval(get_option('ipccp_user_cron')))	{
					update_option('ipccp_recluster', "1");
		}
	}
	//////////////////////////////////////////////////////////////////////////////
	// _wpHead()  ADDING REFERENCE TO LIGHTBOX JS IN HEADER OF HTML				//
	// 			  THE JS WILL ADD THE CS-SHEET SO THERE IS NO NEED HERE			//
	//////////////////////////////////////////////////////////////////////////////
	function _wpHead() {
		echo "\n";
		echo '<!-- START Wp-IPCCP -->';
		echo '<script type="text/javascript" src="'.$this->plugin_path_URL.'/zoom/zoom.js"></script>';
		echo '<!-- END Wp-IPCCP -->';
		echo "\n";
	}
	//////////////////////////////////////////////////////////////////////////////
	// _wpHead()  ADDING REFERENCE TO LIGHTBOX JS IN HEADER OF HTML				//
	// 			  THE JS WILL ADD THE CS-SHEET SO THERE IS NO NEED HERE			//
	//////////////////////////////////////////////////////////////////////////////
	function _wpFoot() {
		if($this->user_use_imagemap)	{
			$output.="\n\n";
			$output.=get_option('ipccp_imagemap_min');
			$output.="\n\n";
			$output.=get_option('ipccp_imagemap_max');
		}
		echo $this->_substitute($output);
	}	
	//////////////////////////////////////////////////////////
	// LODING BASE IMAGE									//
	//////////////////////////////////////////////////////////
	function _LoadJPG($imgname) {
		$im=@ImageCreateFromJPEG($imgname);
		if (!$im) {
			$im = imagecreatetruecolor (600, 50);
			$yellow = ImageColorAllocate ($im, 255, 255, 0);
			$red = ImageColorAllocate ($im, 255, 0, 0);
			$tc = ImageColorAllocate ($im, 0, 0, 0);
			ImageFilledRectangle ($im, 0, 0, 600, 50, $red); 
			ImageFilledRectangle ($im, 5, 5, 595, 45, $yellow); 
			ImageString($im, 1, 5, 20, "Fehler beim Offnen von:", $tc); 
			ImageString($im, 1, 5, 30, "$imgname", $tc); 
		}
		return $im;
	}

	//////////////////////////////////////////////////////////
	// RETURNS PIX Y-VALUE FOR A GIVEN LONGITUDE			//
	//////////////////////////////////////////////////////////
	function _Lon2X($lon)	{return ($this->LON0+$lon*($this->LON90-$this->LON0)/90);}

	//////////////////////////////////////////////////////////
	// RETURNS PIX X-VALUE FOR A GIVEN LATITUDE				//
	//////////////////////////////////////////////////////////
	function _Lat2Y($lat)	{return ($this->LAT0-$lat*($this->LAT0-$this->LAT60)/60);}
	
	//////////////////////////////////////////////////////////
	// RETURNS DISTANCE IN PIX BETWEEN TWO PLACES			//
	//////////////////////////////////////////////////////////
	function _PixDist($foo_x,$foo_y,$bar_x,$bar_y)	{return sqrt(($foo_x-$bar_x)*($foo_x-$bar_x)+($foo_y-$bar_y)*($foo_y-$bar_y));}

	//////////////////////////////////////////////////////////
	// RETURNS CLUSTER SIZE IN PIX DEPENDING ON VISITS		//
	//////////////////////////////////////////////////////////	
	function _DotSize($vis)	{
		if(!$this->user_which_cluster_steps)	{
			if($vis<$this->user_DS_LOW)						{return array($this->user_DS_MIN,0);}
			elseif($vis>=$this->user_DS_HIGH)				{return array($this->user_DS_MAX,$this->user_DS_STEPS+1);}
			else {for($i=1;$i<=$this->user_DS_STEPS;$i++)	{
				if($vis < ceil($this->user_DS_LOW + $i*$this->pplps))	{
					return array(($this->user_DS_MIN + $i * ($this->user_DS_MAX-$this->user_DS_MIN) / ($this->user_DS_STEPS+1)),$i);}}}
		}
		elseif(count($this->user_cluster_steps)>0)	{
			$fSTEPS=count($this->user_cluster_steps);
			if($vis<$this->user_cluster_steps[0])				{return array($this->user_DS_MIN,0);}
			elseif($vis>=$this->user_cluster_steps[$fSTEPS-1])	{return array($this->user_DS_MAX,$fSTEPS);}
			else	{
				for($i=1;$i<=count($this->user_cluster_steps);$i++)	{
					if($vis < $this->user_cluster_steps[$i])	{
						return array(($this->user_DS_MIN + $i * ($this->user_DS_MAX-$this->user_DS_MIN) / count($this->user_cluster_steps)),$i);
					}
				}
			}
		}
		return array(-1,-1);  # OOOPS, SOMETHING WENT WRONG
	}
	
	//////////////////////////////////////////////////////////
	// LEGEND DRAWING										//
	//////////////////////////////////////////////////////////	
	function _myLegend($im)	{
		$ll_x=1850;
		$ll_y=995;
		$border=2;
		$local000 = ImageColorAllocate ($im, 0, 0, 0);
		$localFFF = ImageColorAllocate ($im, 255, 255, 255);
		
		$outer = $this->_ImageColorAllocateFromHex($im,  $this->user_Color_Dot_Outer);
		if(!$this->user_which_cluster_steps)	{
			$hex_colors = $this->_MultiColorFade(array($this->user_Color_Dot_InnerMin,$this->user_Color_Dot_InnerMax), $this->user_DS_STEPS+2);
			for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{$inner[$i] = $this->_ImageColorAllocateFromHex($im,$hex_colors[$i]);}
		}
		else	{
			$hex_colors = $this->_MultiColorFade(array($this->user_Color_Dot_InnerMin,$this->user_Color_Dot_InnerMax), count($this->user_cluster_steps)+1);
			for($i=0;$i<count($this->user_cluster_steps)+1;$i++)	{$inner[$i] = $this->_ImageColorAllocateFromHex($im,$hex_colors[$i]);}
		}

		$width=0;
		$width1=0;
		$width2=0;
		$height=0;

		if(!$this->user_which_cluster_steps)	{
			if($this->user_DS_STEPS<=6)	{
				for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{
					$size=$this->_DotSize(ceil($this->user_DS_LOW+($i)*$this->pplps)-1);
					if($i==0)	{$str_POP="< ".$this->user_DS_LOW;}
					elseif($i==$this->user_DS_STEPS+1)	{$str_POP=">= ".ceil($this->user_DS_HIGH);}
					else	{$str_POP=ceil($this->user_DS_LOW+($i-1)*$this->pplps)."-".ceil($this->user_DS_LOW+($i)*$this->pplps-1);}
					$width+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
				}
				$height=1*$this->user_DS_MAX+20;
			}
			else	{
				for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{
					$size=$this->_DotSize(ceil($this->user_DS_LOW+($i)*$this->pplps)-1);
					if($i==0)	{$str_POP="< ".$this->user_DS_LOW;}
					elseif($i==$this->user_DS_STEPS+1)	{$str_POP=">= ".ceil($this->user_DS_HIGH);}
					else	{$str_POP=ceil($this->user_DS_LOW+($i-1)*$this->pplps)."-".ceil($this->user_DS_LOW+($i)*$this->pplps-1);}
					if($i<=intval($this->user_DS_STEPS/2-(1-$this->user_DS_STEPS%2))+1)	{$width1+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;}
					else	{$width2+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;}
				}
				$height=2*$this->user_DS_MAX+20;
				$width=max($width1,$width2);
				$diff=max($width1-$width2,$width2-$width1);
			}
		}
		else	{
			$fc=count($this->user_cluster_steps);
			if($fc<=5)	{
				for($i=0;$i<$fc+1;$i++)	{
					if($i==0)	{
						$size=$this->_DotSize($this->user_cluster_steps[0]-1);
						$str_POP="< ".($this->user_cluster_steps[0]);
					}
					elseif($i==$fc)	{
						$size=$this->_DotSize($this->user_cluster_steps[$fc-1]+1);
						$str_POP=">= ".($this->user_cluster_steps[$fc-1]);					
					}
					else	{
						$size=$this->_DotSize($this->user_cluster_steps[$i]-1);
						$str_POP=($this->user_cluster_steps[$i-1])."-".($this->user_cluster_steps[$i]-1);
					}
					$width+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
				}
				$height=1*$this->user_DS_MAX+20;
			}
			else	{
				for($i=0;$i<$fc+1;$i++)	{
					if($i==0)	{
						$size=$this->_DotSize($this->user_cluster_steps[0]-1);
						$str_POP="<".($this->user_cluster_steps[0]);
					}
					elseif($i==$fc)	{
						$size=$this->_DotSize($this->user_cluster_steps[$fc-1]+1);
						$str_POP=">= ".($this->user_cluster_steps[$fc-1]);					
					}
					else	{
						$size=$this->_DotSize($this->user_cluster_steps[$i]-1);
						$str_POP=($this->user_cluster_steps[$i-1])."-".($this->user_cluster_steps[$i]-1);
					}
					if($i<=intval($fc/2+$fc%2))	{$width1+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;}
					else						{$width2+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;}
				}
				$height=2*$this->user_DS_MAX+20;
				$width=max($width1,$width2);
				$diff=max($width1-$width2,$width2-$width1);
			}
		}

		$width+=$this->user_DS_MAX;
		ImageFilledRectangle ($im, $ll_x-$width-$border-10, $ll_y-$height-$border, $ll_x+$border, $ll_y+$border, $local000); 
		ImageFilledRectangle ($im, $ll_x-$width-10, $ll_y-$height, $ll_x, $ll_y, $localFFF); 
		ImageString($im, 5, $ll_x-$width+5-10, $ll_y-$height, "Legend:", $local000);

		switch($this->user_which_clustering)		{
			case 0:
				$FooString="Clustering Off";
				break;
			case 1:
				$FooString="Equi-Distant Clustering (".$this->user_CDist."px)";
				break;
			case 2:
				$FooString="Smart Clustering (SCC: ".$this->user_SCC.")";
				break;
			default:
				$FooString="Something's wrong here...";
				break;
		}
		ImageString($im, 2, $ll_x-$width+75, $ll_y-$height+2, $FooString, $local000);
	
		if(!$this->user_which_cluster_steps)	{
			if($this->user_DS_STEPS<=6)	{
				for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{
					$size=$this->_DotSize(ceil($this->user_DS_LOW+($i)*$this->pplps)-1);
					if($i==0)	{$str_POP="< ".$this->user_DS_LOW;}
					elseif($i==$this->user_DS_STEPS+1)	{$str_POP=">= ".ceil($this->user_DS_HIGH);}
					else	{$str_POP=ceil($this->user_DS_LOW+($i-1)*$this->pplps)."-".ceil($this->user_DS_LOW+($i)*$this->pplps-1);}
					$dX+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
					imagefilledellipse($im, $ll_x-$dX, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
					imagefilledellipse($im, $ll_x-$dX, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0], $size[0], $inner[$size[1]]);
					ImageString($im, 2, $ll_x-$dX+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-$this->user_DS_MAX/2-6-3, $str_POP, $local000);
				}
			}
			else	{
				for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{
					$size=$this->_DotSize(ceil($this->user_DS_LOW+($i)*$this->pplps)-1);
					if($i==0)	{$str_POP="< ".$this->user_DS_LOW;}
					elseif($i==$this->user_DS_STEPS+1)	{$str_POP=">= ".ceil($this->user_DS_HIGH);}
					else	{$str_POP=ceil($this->user_DS_LOW+($i-1)*$this->pplps)."-".ceil($this->user_DS_LOW+($i)*$this->pplps-1);}
					if($i<=intval($this->user_DS_STEPS/2-(1-$this->user_DS_STEPS%2))+1)	{
						$dX+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval(1.5*$this->user_DS_MAX)-3, $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval(1.5*$this->user_DS_MAX)-3, $size[0], $size[0], $inner[$size[1]]);
						ImageString($im, 2, $ll_x-$dX-$diff+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-intval(1.5*$this->user_DS_MAX)-6-3, $str_POP, $local000);
					}
					else	{
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0], $size[0], $inner[$size[1]]);
						ImageString($im, 2, $ll_x-$dX-$diff+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-$this->user_DS_MAX/2-6-3, $str_POP, $local000);
						$dX-=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
					}
				}
			}
		}
		else	{
			$fc=count($this->user_cluster_steps);
			if($fc<=5)	{
				for($i=0;$i<$fc+1;$i++)	{
					if($i==0)	{
						$size=$this->_DotSize($this->user_cluster_steps[0]-1);
						$str_POP="< ".($this->user_cluster_steps[0]);
					}
					elseif($i==$fc)	{
						$size=$this->_DotSize($this->user_cluster_steps[$fc-1]+1);
						$str_POP=">= ".($this->user_cluster_steps[$fc-1]);					
					}
					else	{
						$size=$this->_DotSize($this->user_cluster_steps[$i]-1);
						$str_POP=($this->user_cluster_steps[$i-1])."-".($this->user_cluster_steps[$i]-1);
					}
					$dX+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
					imagefilledellipse($im, $ll_x-$dX, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0], $size[0], $inner[$size[1]]);
					ImageString($im, 2, $ll_x-$dX+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-$this->user_DS_MAX/2-6-3, $str_POP, $local000);
				}
			}
			else	{
				for($i=0;$i<$fc+1;$i++)	{
					if($i==0)	{
						$size=$this->_DotSize($this->user_cluster_steps[0]-1);
						$str_POP="< ".($this->user_cluster_steps[0]);
					}
					elseif($i==$fc)	{
						$size=$this->_DotSize($this->user_cluster_steps[$fc-1]+1);
						$str_POP=">= ".($this->user_cluster_steps[$fc-1]);					
					}
					else	{
						$size=$this->_DotSize($this->user_cluster_steps[$i]-1);
						$str_POP=($this->user_cluster_steps[$i-1])."-".($this->user_cluster_steps[$i]-1);
					}
					if($i<=intval($fc/2+$fc%2))	{
						$dX+=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval(1.5*$this->user_DS_MAX)-3, $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval(1.5*$this->user_DS_MAX)-3, $size[0], $size[0], $inner[$size[1]]);
						ImageString($im, 2, $ll_x-$dX-$diff+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-intval(1.5*$this->user_DS_MAX)-6-3, $str_POP, $local000);
					}
					else	{
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
						imagefilledellipse($im, $ll_x-$dX-$diff, $ll_y-intval($this->user_DS_MAX/2)-3, $size[0], $size[0], $inner[$size[1]]);
						ImageString($im, 2, $ll_x-$dX-$diff+$this->user_Border_Dot_Outer+ceil($size[0]/2)+5, $ll_y-$this->user_DS_MAX/2-6-3, $str_POP, $local000);
						$dX-=strlen($str_POP)*6+intval(3*$size[0]/2)+10;
					}
				}
			}
		}
	}
	//////////////////////////////////////////////////////////
	// ALLOCATING COLLORS FROM HEX VALUE					//
	//////////////////////////////////////////////////////////
	function _ImageColorAllocateFromHex($img, $hexstr)	{
		$int = hexdec($hexstr);
		return ImageColorAllocate ($img,
			0xFF & ($int >> 0x10),
			0xFF & ($int >> 0x8),
			0xFF & $int);
	}
	
	//////////////////////////////////////////////////////////
	// RETURNS HEX COLOR ARRAY FOR FADING IN $steps STEPS 	//
	//////////////////////////////////////////////////////////
	function _MultiColorFade($hex_array, $steps) {
		$tot = count($hex_array);
		$gradient = array();
		$fixend = 2;
		$passages = $tot-1;
		$stepsforpassage = floor($steps/$passages);
		$stepsremain = $steps - ($stepsforpassage*$passages);
		for($pointer = 0; $pointer < $tot-1 ; $pointer++) {
			$hexstart = $hex_array[$pointer];
			$hexend = $hex_array[$pointer + 1];
			if($stepsremain > 0){
				if($stepsremain--){$stepsforthis = $stepsforpassage + 1;}
			}else{$stepsforthis = $stepsforpassage;}
			if($pointer > 0){ $fixend = 1;}
			$start['r'] = hexdec(substr($hexstart, 0, 2));
			$start['g'] = hexdec(substr($hexstart, 2, 2));
			$start['b'] = hexdec(substr($hexstart, 4, 2));
			$end['r'] = hexdec(substr($hexend, 0, 2));
			$end['g'] = hexdec(substr($hexend, 2, 2));
			$end['b'] = hexdec(substr($hexend, 4, 2));
			$step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
			$step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
			$step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);
			for($i = 0; $i <= $stepsforthis-$fixend; $i++) {
				$rgb['r'] = floor($start['r'] - ($step['r'] * $i));
				$rgb['g'] = floor($start['g'] - ($step['g'] * $i));
				$rgb['b'] = floor($start['b'] - ($step['b'] * $i));
				$hex['r'] = sprintf('%02x', ($rgb['r']));
				$hex['g'] = sprintf('%02x', ($rgb['g']));
				$hex['b'] = sprintf('%02x', ($rgb['b']));
				$gradient[] = strtoupper(implode(NULL, $hex));
			}
		}
		$gradient[] = $hex_array[$tot-1];
		return $gradient;
	}
	
	//////////////////////////////////////////////////////////
	// DRAWING DASHED CORSSHAIR AT USER DEFINED LONG/LAT	//
	//////////////////////////////////////////////////////////
	function _DashedCrossHair($im,$lon,$lat)	{
		$local = $this->_ImageColorAllocateFromHex($im, $this->user_Color_Crosshair);
		imagedashedline($im,$this->_Lon2X($lon),0,$this->_Lon2X($lon),1000,$local);
		imagedashedline($im,$this->_Lon2X($lon)+1,0,$this->_Lon2X($lon)+1,1000,$local);
		imagedashedline($im,0,$this->_Lat2Y($lat),2000,$this->_Lat2Y($lat),$local);
		imagedashedline($im,0,$this->_Lat2Y($lat)+1,2000,$this->_Lat2Y($lat)+1,$local);
	}

	//////////////////////////////////////////////////////////
	// 														//
	//////////////////////////////////////////////////////////
	function _decode($str,$charset) {
		return strtolower($charset) == 'utf-8' ? utf8_encode($str) : $str;
#		return utf8_encode($str);
	}
	
	//////////////////////////////////////////////////////////
	// 														//
	//////////////////////////////////////////////////////////
	function _forcedStatusPrinting($str,$mode)	{
		if($this->VERBOSE)	{
			if($mode==0)	{
				$time=time()-intval(get_option('ipccp_last_run_time_start'));
				if($time<10)		{print("&nbsp;&nbsp;".$time."s &gt;&gt; ");}
				elseif($time<100)	{print("&nbsp;".$time."s &gt;&gt; ");}
				else				{print($time."s &gt;&gt; ");}
				print($str);
			}
			if($mode==1)	{print($str);}
			ob_flush();
			flush();
		}
	}
	
	//////////////////////////////////////////////////////////////
	// LOGFILE FILTERING - MODIFY FREELY TO SUIT YOUR NEEDS		//
	//////////////////////////////////////////////////////////////
	function _ip_filter($logfile,$filters,$mode)	{
		$nF=count($logfile);
		for($i=0;$i<$nF;$i++)	{
			$go=0;
			foreach($filters as $filter)	{
				$foo="/$filter/";
				if(preg_match($foo,$logfile[$i]))	{$go=1;}  					# GO IF ANY KEYWORD MATCHES
			}
			if($go)	{
				$logfile[$i] = preg_replace('#(^.*?)(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(.*$)#', '$2.$3.$4.$5', $logfile[$i]);
				if(preg_match('#^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#', $logfile[$i]))	{$IPs[count($IPs)]=$logfile[$i];}
			}
			unset($logfile[$i]);												# FREEING UNUSED MEMORY
			if(($i+1)%100000==0)	{
				if($mode==0)	{$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".($i+1)." database entries processed (".count($IPs)." relevant IPs found)<br />",1);}
				else			{$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".($i+1)." logfile lines processed (".count($IPs)." relevant IPs found)<br />",1);}
			}
		}
		return $IPs;
	}
	
	//////////////////////////////////////////////////////////////////////////////
	//	FETCHES IPS AND DRAWS THE CLUSTER MAP 									//
	//			- SAVES THREE IMAGES AND THE IMAGE MAPS 						//
	//////////////////////////////////////////////////////////////////////////////
	function _recluster() {
		if(intval(get_settings('ipccp_recluster'))==1)	{						# VERBOSITY ONLY
			$this->VERBOSE=1;													# IF RECLUSTERING
			update_option('ipccp_recluster', "0");								# CALLED FROM ADMIN
		}																		# PANEL
		else	{$this->VERBOSE=0;}
		update_option('ipccp_last_run_time_start', time());            		 	# WRITING TIME OF LAST CALL TO _recluster()
		require_once "GeoIP/GeoIP.php";											# DOING IT AT THE BEGINNING SO THAT WE WONT
																				# DO OUR WORK MULTIPLE TIMES IF PPL RELOAD
		$this->_get_setting();													# OR ACCESS THE PAGE AT THE SAME TIME WHILE
		$this->factor_of_scaling_min=$this->user_image_width_min/2000.0;		# THE SCHEDULED TASK IS DUE BUT THE FIRST
		$this->factor_of_scaling_max=$this->user_image_width_max/2000.0;		# CALL HASN'T FINISHED IT'S EVALUATION
		$this->pplps=($this->user_DS_HIGH-$this->user_DS_LOW)/($this->user_DS_STEPS);
		
		if($this->user_delay)	{set_time_limit($this->user_time_limit);}		# TRY TO SET DELAY - NEEDS SAFEMODE OFF !
		
		$this->_forcedStatusPrinting("<strong>Cluster Map Generation Started</strong><br /><code style=\"color:#000;\">",1);
		
		###################
		## GETTING READY ##
		###################
		if($this->user_memory_efficient)	{									# SAVE MEMORY BY CALLING Net_GeoIp::STANDARD
			$this->_forcedStatusPrinting("Setting up GeoIP",0);
			$geoip_country 	= Net_GeoIP::getInstance($this->plugin_path_unix."GeoIP/GeoIP.dat",Net_GeoIp::STANDARD);
			$geoip_city 	= Net_GeoIP::getInstance($this->plugin_path_unix."GeoIP/GeoLiteCity.dat",Net_GeoIp::STANDARD);		
		}
		else	{																# GET BETTER PERFOMANCE BY CACHING THE WHOLE DATABASE IN MEMORY
			$this->_forcedStatusPrinting("Loading GeoIP into memory",0);
			$geoip_country 	= Net_GeoIP::getInstance($this->plugin_path_unix."GeoIP/GeoIP.dat",Net_GeoIp::MEMORY_CACHE);
			$geoip_city 	= Net_GeoIP::getInstance($this->plugin_path_unix."GeoIP/GeoLiteCity.dat",Net_GeoIp::MEMORY_CACHE);
		}
		$im_clustered=$this->_LoadJPG($this->loadFile);
		$this->_forcedStatusPrinting("   DONE<br />",1);
		
		##############################################
		## FETCHING IPS FROM DATABASE (OR FILE)		##
		##############################################
		if($this->user_table_key!='' && $this->user_table_name!='')	{
			$sql = 'SELECT '.$this->user_table_key.' FROM '.$this->user_table_name;
			$this->_forcedStatusPrinting("Fetching database entries ($sql)",0);
			$wpdb =& $GLOBALS['wpdb'];
			$database_entries=$wpdb->get_col($sql);
			$this->_forcedStatusPrinting("  DONE (".count($database_entries)." entries)<br />",1);
		}
		$database_entries=$this->_ip_filter($database_entries,$this->user_filter_includes,0);
		
		if($this->user_read_from_file)	{
			$this->_forcedStatusPrinting("Beginning to process logfile ($this->user_file_name)<br />",0);
			$handle = @fopen($this->user_file_name, "r");						# A WORD ON FILE OPENING:
			$nlines=0;															# WITH LARGE FILES DONT USE file()
			if ($handle) {														# OR fgets() - THEY ARE BOTH SLOW
				while (!feof($handle)) {										# EVEN WITH THE EXTRA HASSLE
					$logfile_entries[$nlines++] = fscanf($handle, "%[ -~]");	# OF HAVING AN ARRAY AS RETURN VALUE
					$logfile_entries[$nlines-1]=$logfile_entries[$nlines-1][0];	# SCANF IS SIMPLY THE FASTEST
					if(($nlines)%200000==0)	{
						$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$nlines." lines read<br />",1);
					}
				}
				fclose($handle);
				$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".count($logfile_entries)." lines read<br />",1);
				$logfile_entries=$this->_ip_filter($logfile_entries,$this->user_filter_includes,1);
				$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".count($logfile_entries)." entries kept<br />",1);
			}
			else	{
				$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font style=\"font-weight:bold;color:#f00;\">Couldn't open file</font><br />",1);
			}
		}
		
		$num_db=count($database_entries);
		$num_log=count($logfile_entries);
		$num=$num_db+$num_log;
		$this->_forcedStatusPrinting("<strong>finished fetching ".$num." entries</strong><br />",0);
		
		
		if($num>0)	{												# PROCEED ONLY IF WE HAVE SOME ENTRIES
			$this->_forcedStatusPrinting("Grouping entries by unique IPs <br />",0);		
			for ($i = 0; $num_db > $i; $i++)	{
				$uniq_ip[$database_entries[$i]]++;
					if(($i+1)%500000==0)	{
						$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".($i+1)." database entries done (".count($uniq_ip)." uniqus so far)<br />",1);
					}
			}
			for ($i = 0; $num_log > $i; $i++)	{
				$uniq_ip[$logfile_entries[$i]]++;
					if(($i+1)%500000==0)	{
						$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".($i+1)." logfile entries done (".count($uniq_ip)." uniqus so far)<br />",1);
					}
			}
			$this->_forcedStatusPrinting("Grouping entries by unique IPs DONE (".count($uniq_ip)." unique IPs)<br />",0);
			
			
			##################################################################
			## ACCUMULATING IPS FROM SAME PLACES INTO SINGLE UNIQUE ENTRY	##
			##################################################################
			$nStatusCounter=1;
			$this->_forcedStatusPrinting("Beginning with IP lookup<br />",0);
			$charset=get_settings('blog_charset');
			foreach($uniq_ip as $key => $value)	{									# KEEPING CITY NAME EVEN IF IMAGE MAPS ARE OFF
				$country_name = $geoip_country->lookupCountryName($key);			# TO REDUCE RUNTIMES (LESS IF CASES)
				$location = 	$geoip_city->lookupLocation($key);					# MEMORY IS CHEAP ;)
				if($this->_decode($location->city,$charset)=='')	{$uniq_places_city["$location->longitude"]["$location->latitude"]="unknown";}
				else	{$uniq_places_city["$location->longitude"]["$location->latitude"]=$this->_decode($location->city,$charset);}
				$uniq_places_cntr["$location->longitude"]["$location->latitude"]=$this->_decode($country_name,$charset);
				if($uniq_places_visits["$location->longitude"]["$location->latitude"]=='')	{$uniq_places_counter++;}
				$uniq_places_visits["$location->longitude"]["$location->latitude"]+=$value;
				$nStatusCounter++;
				if(($nStatusCounter)%10000==0)	{$this->_forcedStatusPrinting("yes I'm still working...<br />",0);}
				else if(($nStatusCounter)%1000==0)	{$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$nStatusCounter." IPs done<br />",1);}
			}
			$this->_forcedStatusPrinting("<strong>finished grouping IPs into ".$uniq_places_counter." unique places</strong><br />",0);
		
			##########################################################
			## PREPARING TO GET STUFF SORTED (BY NUMBER OF HITS)	##
			##########################################################
			$this->_forcedStatusPrinting("Rearranging some values",0);
			foreach($uniq_places_visits as $key_O => $value_O) {foreach($uniq_places_visits[$key_O] as $key_A => $value_A) {$UPVV["$value_A"]["$key_O"]["$key_A"]=$uniq_places["$key_O"]["$key_A"];}}
			$this->_forcedStatusPrinting("  DONE<br />",1);
			
			######################################################
			## SORTING BY VISITS AND CLUSTERING SINGLE ENTRIES	##
			######################################################
			$this->_forcedStatusPrinting("Beginning to sort places by number of visits",0);
			krsort($UPVV);															# SORTING BY VALUE - BIGGER CLUSTER WILL GET EVALUATED FIRST
																					# AND DRAWN BENEATH SMALLER ONES
			$clustered_value_total["0"]["0"]=0;										# AVOIDING ERROR MSG ON FIRST CALL OF foreach($clustered...
			$this->_forcedStatusPrinting("  DONE<br />",1);
			$this->_forcedStatusPrinting("Beginning clustering<br />",0);
			foreach($UPVV as $key_V => $value_V)	{
				if($this->user_which_clustering==1)		{$dist=$this->user_CDist;}
				else									{$dist=0;}
				foreach($UPVV[$key_V] as $key_O => $value_O)	{
					foreach($UPVV[$key_V][$key_O] as $key_A => $value_A)	{
						$bClus=0;
						foreach($clustered_value_total as $cluster_key_O => $cluster_value_O)	{
							foreach($clustered_value_total[$cluster_key_O] as $cluster_key_A => $cluster_value_A)	{
								if($this->user_which_clustering==2)		{
									if($cluster_value_A>$this->user_DS_HIGH)	{$cap=$this->user_DS_HIGH;}
									else										{$cap=$cluster_value_A;}
									$dist=intval($this->user_SCC*($this->user_DS_MIN+($this->user_DS_MAX-$this->user_DS_MIN)*sqrt(($cap-$this->user_DS_LOW)/($this->user_DS_HIGH-$this->user_DS_LOW))));
								}
								if($this->_PixDist($this->_Lon2X($key_O),$this->_Lat2Y($key_A),$this->_Lon2X($cluster_key_O),$this->_Lat2Y($cluster_key_A)) < $dist && !$bClus)	{
									$clustered_value_total["$cluster_key_O"]["$cluster_key_A"]+=$key_V;
									$clustered_names["$cluster_key_O"]["$cluster_key_A"][$uniq_places_cntr["$key_O"]["$key_A"]][$uniq_places_city["$key_O"]["$key_A"]]+=$key_V;
									$bClus=1;										# CLUSTER EXIST ALREADY
								}
							}
						}
						if(!$bClus)	{												# NEW CLUSTER FOUND!
							$clustered_value_total["$key_O"]["$key_A"]=$key_V;
							$clustered_names["$key_O"]["$key_A"][$uniq_places_cntr["$key_O"]["$key_A"]][$uniq_places_city["$key_O"]["$key_A"]]=$key_V;
							$cluster_counter++;
							if(($cluster_counter)%50==0)	{
								if($cluster_counter<100)	{$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$cluster_counter." cluster<br />",1);}
								else						{$this->_forcedStatusPrinting("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$cluster_counter." cluster<br />",1);}
							}
						}
					}
				}
			}
			$this->_forcedStatusPrinting("<strong>finished clustering into ".$cluster_counter." cluster</strong><br />",0);
			##########################
			## ALLOCATING COLORS	##
			##########################
			$outer = $this->_ImageColorAllocateFromHex($im_clustered,  $this->user_Color_Dot_Outer);
			if(!$this->user_which_cluster_steps)	{
				$hex_colors = $this->_MultiColorFade(array($this->user_Color_Dot_InnerMin,$this->user_Color_Dot_InnerMax), $this->user_DS_STEPS+2);
				for($i=0;$i<$this->user_DS_STEPS+2;$i++)	{$inner[$i] = $this->_ImageColorAllocateFromHex($im_clustered,$hex_colors[$i]);}
				$this->_forcedStatusPrinting("Finished allocation ".($this->user_DS_STEPS+2)." colors<br />",0);
			}
			else	{
				$hex_colors = $this->_MultiColorFade(array($this->user_Color_Dot_InnerMin,$this->user_Color_Dot_InnerMax), count($this->user_cluster_steps)+1);
				for($i=0;$i<count($this->user_cluster_steps)+1;$i++)	{$inner[$i] = $this->_ImageColorAllocateFromHex($im_clustered,$hex_colors[$i]);}
				$this->_forcedStatusPrinting("Finished allocation ".(count($this->user_cluster_steps)+1)." colors<br />",0);
			}



			######################################################
			## DRAWING CROSSHAIR & CLUSERS INTO LOADED IMAGE	##
			######################################################
			$this->_forcedStatusPrinting("Beginning to draw",0);
			$this->imagemap_max='<area shape="rect" coords="'.intval($this->factor_of_scaling_max*400).','.intval($this->factor_of_scaling_max*985).','.intval($this->factor_of_scaling_max*1200).','.intval($this->factor_of_scaling_max*1000).'" href="http://blog.vimagic.de/ipccp" title="Visit IPCCP Homepage" /></map>';
			$this->imagemap_min='<area shape="rect" coords="'.intval($this->factor_of_scaling_min*400).','.intval($this->factor_of_scaling_min*985).','.intval($this->factor_of_scaling_min*1200).','.intval($this->factor_of_scaling_min*1000).'" href="http://blog.vimagic.de/ipccp" title="Visit IPCCP Homepage" /><area shape="rect" coords="0,0,'.$this->user_image_width_min.','.($this->factor_of_scaling_min*1000.0).'" href="%IPCCP_MAX%" rel="ipccp" alt ="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Click to zoom" title="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Click to zoom" title_zoomed="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Hover over the clusters for names, exit by pressing ESC or clicking the upper left icon" /></map>';
			if($this->user_add_my_place)	{$this->_DashedCrossHair($im_clustered,$this->user_my_place_long,$this->user_my_place_lat);}
			foreach($clustered_names as $key_O => $value_O)	{
				foreach($clustered_names[$key_O] as $key_A => $value_A)	{
					if($key_O!='0' && $key_A!='0')	{
						foreach($clustered_names[$key_O][$key_A] as $Lk => $Lv)	{
							$CLUSER_LIST["$key_O"]["$key_A"].="===== &nbsp;&nbsp;&nbsp;".$Lk."&nbsp;&nbsp;&nbsp;=====\n";
							$last_Sv=0;
							$high_Sv=0;
							$nCities=0;
							$rest=0;
							arsort($clustered_names[$key_O][$key_A][$Lk]);
							foreach($clustered_names[$key_O][$key_A][$Lk] as $Sk => $Sv)	{
								if($Sv>$high_Sv)	{$high_Sv=$Sv;}
								if(($Sv/$high_Sv > 0.01 || $nCities<20) && $nCities<40)	{
									if ($last_Sv==0)		{$CLUSER_LIST["$key_O"]["$key_A"].="$Sk";}
									else if($Sv==$last_Sv)	{$CLUSER_LIST["$key_O"]["$key_A"].=", $Sk";}
									else					{$CLUSER_LIST["$key_O"]["$key_A"].=" (".$last_Sv.")\n$Sk";}
									$last_Sv=$Sv;
									$nCities++;
								}
								else	{
									$rest+=$Sv;
								}
								$nEntries++;
							}
							$CLUSER_LIST["$key_O"]["$key_A"].=" (".$last_Sv.")\n";
							if($rest>0)	{$CLUSER_LIST["$key_O"]["$key_A"].="Other ($rest)\n";}
						}

						if($nEntries>1)	{$CLUSER_LIST["$key_O"]["$key_A"].="\n".'[Sum: '.$clustered_value_total["$key_O"]["$key_A"].' hits]';}
						$nEntries=0;
						$size=$this->_DotSize(intval($clustered_value_total["$key_O"]["$key_A"]));
						imagefilledellipse($im_clustered, $this->_Lon2X($key_O), $this->_Lat2Y($key_A), $size[0]+2*$this->user_Border_Dot_Outer,$size[0]+2*$this->user_Border_Dot_Outer,$outer);
						imagefilledellipse($im_clustered, $this->_Lon2X($key_O), $this->_Lat2Y($key_A), $size[0], $size[0], $inner[$size[1]]);
						$this->imagemap_max='<area shape="circle" coords="'.intval($this->factor_of_scaling_max*($this->_Lon2X($key_O)+2)).','.intval($this->factor_of_scaling_max*($this->_Lat2Y($key_A)+2)).','.max(intval($this->factor_of_scaling_max*(($size[0]+2*$this->user_Border_Dot_Outer))/2),2).'" href=" " alt="" title="'.$CLUSER_LIST["$key_O"]["$key_A"].'" />'.$this->imagemap_max;
						$this->imagemap_min='<area shape="circle" coords="'.intval($this->factor_of_scaling_min*($this->_Lon2X($key_O)+2)).','.intval($this->factor_of_scaling_min*($this->_Lat2Y($key_A)+2)).','.max(intval($this->factor_of_scaling_min*(($size[0]+2*$this->user_Border_Dot_Outer))/2),2).'" href="%IPCCP_MAX%" rel="ipccp" alt="" title="'.$CLUSER_LIST["$key_O"]["$key_A"].'" />'.$this->imagemap_min;
					}
				}
			}	
			$this->imagemap_min='<map name="IPCCP_MIN" id="IPCCP_MIN">'.$this->imagemap_min;
			$this->imagemap_max='<map name="IPCCP_MAX" id="IPCCP_MAX">'.$this->imagemap_max;
			update_option('ipccp_imagemap_min', $this->imagemap_min);				# IMAGE MAPS ARE BUILD RECURSIVELY SO SMALLER CLUSTER
			update_option('ipccp_imagemap_max', $this->imagemap_max);				# ONTOP OF BIGGER ONES ARE STILL TOUCHABLE

			$this->_forcedStatusPrinting("  DONE<br />",1);
			
			##############################
			## ADDING INFOS & LEGEND	##
			##############################
//			if($this->user_draw_legend==3)	{$this->_myLegend($im_clustered);}
			ImageString($im_clustered, 5, 7, 7, get_option('blogname')." Cluster Map", $blackC);
		} // ENDIF($num>0)
		else	{ImageString($im_clustered, 5, 7, 7, "ATTEMPTED: ".get_option('blogname')." Cluster Map (NO ENTRIES FOUND)", $blackC);}
/* */

			if($this->user_draw_legend==3)	{$this->_myLegend($im_clustered);}

		$foo=explode("/",$this->user_file_name);
		if($this->user_table_key!='' && $this->user_table_name!='' && $this->user_read_from_file)	{ImageString($im_clustered, 2, 7, 22, "Generated from Database and logfile \"".$foo[count($foo)-1]."\"", $blackC);}
		elseif($this->user_table_key!='' && $this->user_table_name!='' && !$this->user_read_from_file) {ImageString($im_clustered, 2, 7, 22, "Generated from Database", $blackC);}
		if($this->user_read_from_file)	{if(count($this->user_filter_includes)>0 && $this->user_filter_includes[0]!='')	{for($k=0;$k<count($this->user_filter_includes);$k++)	{ImageString($im_clustered, 2, 7, (34+$k*12), "  Filter ".($k+1).": ".$this->user_filter_includes[$k], $blackC);}}}
		ImageString($im_clustered, 2, 7, 974, "$num | ".($num-$clustered_value_total[0][0])." | ".count($uniq_ip)." | $uniq_places_counter | $cluster_counter", $blackC); 
		ImageString($im_clustered, 2, 7, 986, "generated for ".get_option('blogname')." @ ".trailingslashit(get_option('home'))." in ".(time()-intval(get_option('ipccp_last_run_time_start')))."s on ".date("D. M-j G:i:s T Y")." | IPCCP ".VERSION." | http://blog.vimagic.de/ipccp", $blackC); 
		
		$this->_forcedStatusPrinting("Finished with additional drawings<br />",0);
		##################################
		## SAVING AND FREEING MEMORY	##
		##################################
		$newwidth=intval($this->user_image_width_max);
		$newheight=intval($this->factor_of_scaling_max*1000.0);
		$foo_file = imagecreatetruecolor($newwidth, $newheight);
		if(imagecopyresized($foo_file,$im_clustered,0,0,0,0,$newwidth,$newheight,2000,1000))	{imagejpeg($foo_file,$this->saveFile_clustered_max,$this->user_jpg_quality);}
		imagedestroy($foo_file);
		$newwidth=intval($this->user_image_width_min);
		$newheight=intval($this->factor_of_scaling_min*1000.0);
		$foo_file = imagecreatetruecolor($newwidth, $newheight);
		if(imagecopyresized($foo_file,$im_clustered,0,0,0,0,$newwidth,$newheight,2000,1000))	{imagejpeg($foo_file,$this->saveFile_clustered_min,$this->user_jpg_quality);}
		imagedestroy($foo_file);		
		imagejpeg($im_clustered,$this->saveFile_clustered);
		imagedestroy($im_clustered);
		update_option('ipccp_last_run_time_finish', time());            		# KEEPING FINISH TIME
		$this->_forcedStatusPrinting("</code><strong>Done generating a new cluster map</strong><br />",1);
		return $cgq_STRING;
	}
	
	//////////////////////////////////////////////////////////////////////////////
	//	OPTION HANDLING															//
	//////////////////////////////////////////////////////////////////////////////
	function _install() {
		add_option('ipccp_user_table_name', $this->user_table_name,'Statistics SQL-Table Name','yes');
		add_option('ipccp_user_table_key', $this->user_table_key,'Statistics SQL-Table IP-Key Name','yes');
		add_option('ipccp_user_add_my_place', $this->user_add_my_place,'Adding crosshair?','yes');		
		add_option('ipccp_user_my_place_long', $this->user_my_place_long,'Crosshair longitude','yes');		
		add_option('ipccp_user_my_place_lat', $this->user_my_place_lat,'corsshair latitude','yes');
		add_option('ipccp_user_DS_MIN', $this->user_DS_MIN,'Minimum Dot size in pixel','yes');
		add_option('ipccp_user_DS_MAX', $this->user_DS_MAX,'Maximum Dot size in pixel','yes');
		add_option('ipccp_user_DS_STEPS', $this->user_DS_STEPS,'Number of steps in between','yes');
		add_option('ipccp_user_DS_LOW', $this->user_DS_LOW,'Lower bound on visits','yes');
		add_option('ipccp_user_DS_HIGH', $this->user_DS_HIGH,'Upper bound on visits','yes');
		add_option('ipccp_user_Border_Dot_Outer', $this->user_Border_Dot_Outer,'Cluster border in pixel','yes');
		add_option('ipccp_user_Color_Crosshair', $this->user_Color_Crosshair,'Crosshair color in hex','yes');
		add_option('ipccp_user_Color_Dot_Outer', $this->user_Color_Dot_Outer,'Cluster border color in hex','yes');
		add_option('ipccp_user_Color_Dot_InnerMax', $this->user_Color_Dot_InnerMax,'Min size cluster color in hex','yes');
		add_option('ipccp_user_Color_Dot_InnerMin', $this->user_Color_Dot_InnerMin,'Max size cluster color in hex','yes');
		add_option('ipccp_user_which_clustering', $this->user_which_clustering,'Which clustering mode?','yes');
		add_option('ipccp_user_SCC', $this->user_SCC,'Smart cluster constant','yes');
		add_option('ipccp_user_CDist', $this->user_CDist,'fixed distance clustering constant in pix','yes');
		add_option('ipccp_user_delay', $this->user_delay,'shall we try to get some more time?','yes');
		add_option('ipccp_user_time_limit', $this->user_time_limit,'time delay in seconds','yes');
		add_option('ipccp_user_template_output', $this->user_template_output,'output template ','yes');
		add_option('ipccp_user_use_imagemap', $this->user_use_imagemap,'shall we add image maps?','yes');
		add_option('ipccp_user_show_small_picture', $this->user_show_small_picture,'which picture to use fro preview','yes');
		add_option('ipccp_user_image_width_min', $this->user_image_width_min,'preview size','yes');
		add_option('ipccp_user_image_width_max', $this->user_image_width_max,'zoom size','yes');
		add_option('ipccp_user_redraw_on_update', $this->user_redraw_on_update,'redraw on update (CURRENTLY NOT USED v0.a4)','yes');
		add_option('ipccp_user_read_from_file', $this->user_read_from_file,'add ips from text file?','yes');
		add_option('ipccp_user_file_name', $this->user_file_name,'ip-textfile','yes');
		add_option('ipccp_user_cron', $this->user_cron,'scheduling time','yes');
		add_option('ipccp_user_filter_includes', implode(",",$this->user_filter_includes),'Include list for filter','yes');
		add_option('ipccp_user_draw_legend', $this->user_draw_legend,'shall we draw legends?','yes');
		add_option('ipccp_user_memory_efficient', $this->user_memory_efficient,'memory efficient or performance?','yes');
		add_option('ipccp_user_jpg_quality', $this->user_jpg_quality,'Quality of the output','yes');
		add_option('ipccp_user_cluster_steps', implode(",",$this->user_cluster_steps),'Cluster Step Sizes','yes');
		add_option('ipccp_user_which_cluster_steps', $this->user_which_cluster_steps,'Which cluster step mode','yes');
		
		add_option('ipccp_imagemap_min', $this->imagemap_max,'image map for preview','yes');
		add_option('ipccp_imagemap_max', $this->imagemap_max,'image map for zoomed picture','yes');
		add_option('ipccp_last_run_time_start', $this->last_run_time_start,'last start time','yes');
		add_option('ipccp_last_run_time_finish', $this->last_run_time_finish,'last finish time','yes');
		add_option('ipccp_status', $this->status,'Current state of the reclustering procedure (CURRENTLY NOT USED v0.b5.2','yes');
		add_option('ipccp_recluster', 0,'Current state of the reclustering procedure','yes');
	}
	function _get_setting() {
		foreach ($this->options as $option => $type) {
			$this->$option = get_option('ipccp_'.$option);
			switch ($type) {
				case 'bool':
				case 'int':
					$this->$option = intval($this->$option);
					break;
				case 'string':
					$value = strval($_POST[$option]);
					break;
				case 'array':
					$this->$option=explode(",",$this->$option); // MAKING ARRAY FROM COMMA SEPARATED STRING
					break;
			}
		}
	}
	function _update_settings() {
		if(intval($_POST['user_jpg_quality'])<0)	{$this->user_jpg_quality=0;$_POST['user_jpg_quality']=0;}
		if(intval($_POST['user_jpg_quality'])>100)	{$this->user_jpg_quality=100;$_POST['user_jpg_quality']=100;}
		
		if(intval($_POST['user_image_width_max'])<0)	{$this->user_image_width_max=0;$_POST['user_image_width_max']=0;}
		if(intval($_POST['user_image_width_max'])>2000)	{$this->user_image_width_max=2000;$_POST['user_image_width_max']=2000;}
		if(intval($_POST['user_image_width_min'])<0)	{$this->user_image_width_min=0;$_POST['user_image_width_min']=0;}
		if(intval($_POST['user_image_width_min'])>2000)	{$this->user_image_width_min=2000;$_POST['user_image_width_min']=2000;}
		if(intval($_POST['user_Border_Dot_Outer'])<0)	{$this->user_Border_Dot_Outer=0;$_POST['user_Border_Dot_Outer']=0;}
		if(intval($_POST['user_DS_MIN'])<0)				{$this->user_DS_MIN=0;$_POST['user_DS_MIN']=0;}
		if(intval($_POST['user_DS_MIN'])>30)			{$this->user_DS_MIN=30;$_POST['user_DS_MIN']=30;}
		if(intval($_POST['user_DS_MAX'])<0)				{$this->user_DS_MAX=0;$_POST['user_DS_MAX']=0;}
		if(intval($_POST['user_DS_MAX'])>50)			{$this->user_DS_MAX=50;$_POST['user_DS_MAX']=50;}
		if(intval($_POST['user_DS_MIN'])>intval($_POST['user_DS_MAX']))
														{$this->user_DS_MAX=intval($_POST['user_DS_MIN']);$_POST['user_DS_MAX']=intval($_POST['user_DS_MIN']);}
		if(intval($_POST['user_DS_HIGH'])<0)			{$this->user_DS_HIGH=0;$_POST['user_DS_HIGH']=0;}
		if(intval($_POST['user_DS_LOW'])<0)				{$this->user_DS_LOW=0;$_POST['user_DS_LOW']=0;}
		if(intval($_POST['user_DS_STEPS'])<1)			{$this->user_DS_STEPS=1;$_POST['user_DS_STEPS']=1;}
		if(intval($_POST['user_DS_STEPS'])>intval($_POST['user_DS_MAX'])-intval($_POST['user_DS_MIN']))
														{$this->user_DS_STEPS=intval($_POST['user_DS_MAX'])-intval($_POST['user_DS_MIN']);$_POST['user_DS_STEPS']=intval($_POST['user_DS_MAX'])-intval($_POST['user_DS_MIN']);}
		if(!isset($_POST['user_add_my_place']))			{$this->user_add_my_place=0;$_POST['user_add_my_place']=0;}
		if(!isset($_POST['user_delay']))				{$this->user_delay=0;$_POST['user_delay']=0;}
		if(!isset($_POST['user_use_imagemap']))			{$this->user_use_imagemap=0;$_POST['user_use_imagemap']=0;}
		if(!isset($_POST['user_show_small_picture']))	{$this->user_show_small_picture=0;$_POST['user_show_small_picture']=0;}
		if(!isset($_POST['user_redraw_on_update']))		{$this->user_redraw_on_update=0;$_POST['user_redraw_on_update']=0;}
		if(!isset($_POST['user_read_from_file']))		{$this->user_read_from_file=0;$_POST['user_read_from_file']=0;}
		if(!isset($_POST['user_memory_efficient']))		{$this->user_memory_efficient=0;$_POST['user_memory_efficient']=0;}
		if(!isset($_POST['user_which_cluster_steps']))		{$this->user_which_cluster_steps=0;$_POST['user_which_cluster_steps']=0;}
		
		foreach ($this->options as $option => $type) {
			if (isset($_POST[$option])) {
				switch ($type) {
					case 'int':
						$value = intval($_POST[$option]);
						break;
					case 'string':
						$value = strval($_POST[$option]);
						break;
					case 'array':
						$value = strval($_POST[$option]);
						break;
					case 'bool':
						if(intval($_POST[$option]))	{$value = 1;}
						else						{$value = 0;}
						break;
					default:
						$value = stripslashes($_POST[$option]);
				}
				update_option('ipccp_'.$option, $value);
			}
			else {update_option('ipccp_'.$option, $this->$option);}
		}
		return;
	}

	//////////////////////////////////////////////////////////////////////////////
	//	PRINTS ADMIN OPTION HTML FORM FOR THE ADMIN OPTION PANEL				//
	// 				.../wordpress/wp-admin/options-general.php?page=ipccp.php	//
	//////////////////////////////////////////////////////////////////////////////	
	function _options_form() {
		if($this->user_add_my_place)		{$add_my_place=' checked';}
		if($this->user_delay)				{$delay=' checked';}
		if($this->user_use_imagemap)		{$imagemap=' checked';}
		if($this->user_show_small_picture)	{$show_smal_picture=' checked';}
		if($this->user_redraw_on_update)	{$user_redraw_on_update=' checked';}
		if($this->user_read_from_file)		{$read_from_file=' checked';}
		if($this->user_memory_efficient)	{$memory_efficient=' checked="checked"';}
		else								{$performance=' checked="checked"';}
		if($this->user_which_cluster_steps)	{$which_cluster_steps_1=' checked="checked"';}
		else								{$which_cluster_steps_0=' checked="checked"';}
	
		switch($this->user_which_clustering)	{
			case 0:
				$cluster_off=' checked="checked"';
				break;
			case 1:
				$cluster_std=' checked="checked"';
				break;
			default:
			case 2:
				$cluster_smart=' checked="checked"';
				break;
		}																		
		switch($this->user_draw_legend)	{
			case 0:
				$legend_off=' checked="checked"';
				break;
			case 1:
				$legend_small=' checked="checked"';
				break;
			case 2:
				$legend_big=' checked="checked"';
				break;
			default:
			case 3:
				$legend_on=' checked="checked"';
		}																		# HTML CODE WILL BE OPTIMIZED ONCE WE ARE OUT OF BETA
		print('
		<form name="ipccp" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
			<input type="hidden" name="ipccp_action" value="ipccp_update_settings" />
			<div class="wrap">
				<h2>'.__('&#187; Basics', 'blog.vimagic.de').'</h2>
				<fieldset class="options">
				<table border="0" cellspacing="5" cellpadding="5">
					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_table_name">'.__('Counter&nbsp;table&nbsp;name:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_table_name" size="15" value="'.$this->user_table_name.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_table_key">'.__('remote IP field name:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_table_key" size="7" value="'.$this->user_table_key.'" />
					</td><td colspan="4" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"></td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_template_output">'.__('Output template:', 'blog.vimaigc.de').'</label></strong>
					</td><td colspan="6" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;"><textarea name="user_template_output" cols="70" rows="5">'.htmlentities($this->user_template_output).'</textarea>
					<td style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Usable Token:<br /> %IPCCP_MIN% / %IPCCP_MAX% URL = of previw and zoomed image<br />%WIDTH_MIN% / %WIDTH_MAX% = width of previw and zoomed image<br /> %IMAGEMAP% = appropriate image map<br />%BLOGNAME% = your blog name</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_show_small_picture">'.__('show small picture:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="checkbox" name="user_show_small_picture" value="1"'.$show_smal_picture.' />
					</td><td colspan="6" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">When checked, %IPCCP% will be substituted for the preview image.  This is not recommended though.  Most browsers have a better scaling procedure than PHP::GD.</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_image_width_min">'.__('Previw&nbsp;Image&nbsp;Width:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_image_width_min" size="7" style="text-align:right;" value="'.$this->user_image_width_min.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_image_width_max">'.__('Zoomed&nbsp;Image&nbsp;Width:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_image_width_max" size="7" style="text-align:right;" value="'.$this->user_image_width_max.'" />
					</td><td colspan="4" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Positive integers smaller 2000px (base image width).</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_jpg_quality">'.__('JPG Quality:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_jpg_quality" size="7" style="text-align:right;" value="'.$this->user_jpg_quality.'" />
					</td><td colspan="6" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">JPG quality of the saved output files - positive Interger between 0 and 100.</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_DS_MIN">'.__('Min Cluster Size:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_DS_MIN" size="7" style="text-align:right;" value="'.$this->user_DS_MIN.'" /> [<font style="color:#bbb;font-weight:bold;font-size:0.75em;"><=30px</font>]
					</td>
					<td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_DS_MAX">'.__('Max Cluster Size:', 'blog.vimaigc.de').'</label></strong>
					</td><td colspan="5" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;border-right:1px #000 dotted;">
					<input type="text" name="user_DS_MAX" size="7" style="text-align:right;" value="'.$this->user_DS_MAX.'" /> [<font style="color:#bbb;font-weight:bold;font-size:0.75em;"><=50px</font>]
					</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_add_my_place">'.__('Add my place:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="checkbox" name="user_add_my_place" value="1"'.$add_my_place.' />
					</td><td colspan="6" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Draws crosshair below clusters at your place.</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_my_place_long">'.__('My place longitude:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_my_place_long" size="7" style="text-align:right;" value="'.$this->user_my_place_long.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_my_place_lat">'.__('My place latitude:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_my_place_lat" size="7" style="text-align:right;" value="'.$this->user_my_place_lat.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_Color_Crosshair">'.__('My&nbsp;Place&nbsp;Color:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_Color_Crosshair" size="7" style="text-align:right;" value="'.$this->user_Color_Crosshair.'" />
					</td><td colspan="2" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"> </td></tr>					

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_Border_Dot_Outer">'.__('Border:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_Border_Dot_Outer" size="7" style="text-align:right;" value="'.$this->user_Border_Dot_Outer.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_Color_Dot_Outer">'.__('Border Color:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_Color_Dot_Outer" size="7" style="text-align:right;" value="'.$this->user_Color_Dot_Outer.'" />
					</td><td colspan="4" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Border size and color in pixel and hex respectively.</font>]</td></tr>
					
					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_Color_Dot_InnerMin">'.__('Inner Min Color:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_Color_Dot_InnerMin" size="7" style="text-align:right;" value="'.$this->user_Color_Dot_InnerMin.'" />
					</td><td align="right" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_Color_Dot_InnerMax">'.__('Inner Max Color:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="text" name="user_Color_Dot_InnerMax" size="7" style="text-align:right;" value="'.$this->user_Color_Dot_InnerMax.'" />
					</td><td colspan="4" style="border-right:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Inner cluster colors for minimum and maximum cluster sizes,</font>]</td></tr>
					
					<tr><td align="right" rowspan="2" valign="middle" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"><strong>
					<label for="user_draw_legend">'.__('Legend:', 'blog.vimaigc.de').'</label></strong></td><td align="left" style="border-top:1px #000 dotted;">
					<input type="radio" name="user_draw_legend" value="0" '.$legend_off.'/> Off
					</td><td colspan="6" rowspan="2" style="border-top:1px #000 dotted;border-right:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">The legend...what else is there to say?</font>]</td></tr>
<!--					<tr><td align="left"><input type="radio" name="user_draw_legend" value="1" '.$legend_small.'/> On Preview only</td></tr>
					<tr><td align="left"><input type="radio" name="user_draw_legend" value="2" '.$legend_big.'/> On Zoom only</td></tr> -->
					<tr><td align="left" style="border-bottom:1px #000 dotted;"><input type="radio" name="user_draw_legend" value="3" '.$legend_on.'/> On</td></tr>
					
				</table>
				</fieldset>
			</div>');
		print('
			<div class="wrap">
				<h2>'.__('&#187; Clustering', 'blog.vimagic.de').'</h2>
				<fieldset class="options">
				<table border="0" cellspacing="5" cellpadding="5">
					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_use_imagemap">'.__('Show&nbsp;Cluster&nbsp;Names:', 'blog.vimaigc.de').'</label></strong>
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="checkbox" name="user_use_imagemap" value="1"'.$imagemap.' />
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">&nbsp;</td><td colspan="5" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;border-right:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Adding both image maps for the small and zoomed picture.</font>]</td></tr>
					
					<tr><td align="right" rowspan="3" valign="middle" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"><strong>
					<label for="user_which_clustering">'.__('Cluster Mode:', 'blog.vimaigc.de').'</label></strong></td><td align="left" style="border-top:1px #000 dotted;">
					<input type="radio" name="user_which_clustering" value="0" '.$cluster_off.'/> off
					</td><td style="border-top:1px #000 dotted;">&nbsp;</td><td colspan="5" style="border-top:1px #000 dotted;border-right:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Disables clustering.</font>]</td></tr>
					<tr><td align="left" style="width:80px;">
					<input type="radio" name="user_which_clustering" value="1" '.$cluster_std.'/> standard
					</td><td align="right" style="width:90px;">Pixel:
					<input type="text" name="user_CDist" size="5" value="'.$this->user_CDist.'" style="text-align:right;"/>
					</td><td colspan="5" style="border-right:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Enables standard clustering: Citys closer than '.$this->user_CDist.' pixel will appear as one cluster.</font>]</td></tr>
					<tr><td align="left" style="border-bottom:1px #000 dotted;">
					<input type="radio" name="user_which_clustering" value="2" '.$cluster_smart.'/> smart
					</td><td align="right" style="border-bottom:1px #000 dotted;">SCC:
					<input type="text" name="user_SCC" size="5" value="'.$this->user_SCC.'" style="text-align:right;"/>
					</td><td colspan="5" style="border-bottom:1px #000 dotted;border-right:1px #000 dotted;">					
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Enables smart clustering: Larger citys will have a greater effect on neighboring citys.  The SmartClusteringConstant will linearly modify the influence.</font>]</td></tr>

					<tr><td align="right" rowspan="2" valign="middle" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"><strong>
					<label for="user_which_cluster_steps">'.__('Cluster Mode<br />(Stepping):', 'blog.vimaigc.de').'</label></strong></td><td align="left" style="border-top:1px #000 dotted;">
					<input type="radio" name="user_which_cluster_steps" value="0" '.$which_cluster_steps_0.'/> EQUI</td>
					<td align="right" style="border-top:1px #000 dotted;"><strong><label for="user_DS_STEPS">'.__('Cluster&nbsp;Steps:', 'blog.vimaigc.de').'</label></strong></td>
					<td style="border-top:1px #000 dotted;"><input type="text" name="user_DS_STEPS" size="7" style="text-align:right;" value="'.$this->user_DS_STEPS.'" /></td>
					<td align="right" style="border-top:1px #000 dotted;"><strong><label for="user_DS_LOW">'.__('Min Visits:', 'blog.vimaigc.de').'</label></strong></td>
					<td style="border-top:1px #000 dotted;"><input type="text" name="user_DS_LOW" size="7" style="text-align:right;" value="'.$this->user_DS_LOW.'" /></td>
					<td align="right" style="border-top:1px #000 dotted;"><strong><label for="user_DS_HIGH">'.__('Max&nbsp;Visits:', 'blog.vimaigc.de').'</label></strong></td>
					<td style="border-top:1px #000 dotted;border-right:1px #000 dotted;" width="100%"><input type="text" name="user_DS_HIGH" size="7" style="text-align:right;" value="'.$this->user_DS_HIGH.'" /></td></tr>
					
					<tr><td align="left" style="border-bottom:1px #000 dotted;"><input type="radio" name="user_which_cluster_steps" value="1" '.$which_cluster_steps_1.'/> USER</td>
					<td align="right" style="border-bottom:1px #000 dotted;"><strong><label for="user_DS_HIGH">'.__('UDC:', 'blog.vimaigc.de').'</label></strong></td>
					<td colspan="5" style="border-right:1px #000 dotted;border-bottom:1px #000 dotted;"><input type="text" name="user_cluster_steps" size="50" value="'.implode(",",$this->user_cluster_steps).'" /></td></tr>

				</table>
				</fieldset>
			</div>');
		print('
			<div class="wrap">
				<h2>'.__('&#187; Advanced', 'blog.vimagic.de').'</h2>
				<fieldset class="options">
				<table border="0" cellspacing="5" cellpadding="5">
					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_delay">'.__('Use delay:', 'blog.vimaigc.de').'</label></strong>
					</td><td width="80" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="checkbox" name="user_delay" value="1"'.$delay.' />
					<input type="text" name="user_time_limit" size="4" value="'.$this->user_time_limit.'" /> s
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;border-right:1px #000 dotted;">					
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Due to the extended evaluation the script might run longer than the maximum PHP execution time (usually 30sec).  Running the script on my old webserver with a dataset of about 2.8mill takes more than 5mins.  Requires that the server <font style="color:#f00;">DOES NOT</font> run PHP in safemode.</font>]</td></tr>
	
					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_cron">'.__('Scheduling&nbsp;time:', 'blog.vimaigc.de').'</label></strong>
					</td><td width="80" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;width:80px;">
					<input type="text" name="user_cron" size="6" value="'.$this->user_cron.'" style="text-align:right;" /> s
					</td><td style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;border-right:1px #000 dotted;">					
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">There is three ways to redraw the map.  The first way is by pressing the &quot;<font style="color:#000;">Update &amp; Redraw</font>&quot; button below.  Secondly it will be redrawn if you add <font style="color:#000;">recluster=true</font> to any URL call [ie http://BLOGURL/PAGEORPOST?recluster=true ].  FInally you can set a defined scheduling interval in seconds, after which the picture will be automatically generated.  To be precise, it will be generated at the next website access after the specified time.  Keep the require CPU time in mind and don\'t set this value too low! Hourly => 3600s, <font style="color:#000;">Daily => 86400s</font>, <font style="color:#000;">Weekly => 604800s</font></font>]<br />[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">UPDATE: The second method by using the URL call watches the scheduling time as well to avoid missuse.</font>]</td></tr>

					<tr><td align="right" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;">
					<strong><label for="user_read_from_file">'.__('Include&nbsp;logfile:', 'blog.vimaigc.de').'</label></strong>
					</td><td colspan="2" style="border-top:1px #000 dotted;border-bottom:1px #000 dotted;border-bottom:1px #000 dotted;">
					<input type="checkbox" name="user_read_from_file" value="1"'.$read_from_file.' /> <input type="text" name="user_file_name" size="100" value="'.$this->user_file_name.'" /><br />
					<input type="text" name="user_filter_includes" size="80" value="'.implode(",",$this->user_filter_includes).'" /><br />
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Enter complete path of the logfile.  Modify the <code>logfile_filter.php</code> to suit your file.  Expected are the return of an array of IPs.</font>]
					</td></tr>
					
					<tr><td align="right" rowspan="2" valign="middle" style="border-left:1px #000 dotted;border-top:1px #000 dotted;border-bottom:1px #000 dotted;"><strong>
					<label for="user_memory_efficient">'.__('Performance<br />vs<br />Memory', 'blog.vimaigc.de').'</label></strong></td><td align="left" style="border-top:1px #000 dotted;">
					<input type="radio" name="user_memory_efficient" value="0" '.$performance.'/> Higher performance
					</td><td colspan="5" rowspan="2" style="border-top:1px #000 dotted;border-right:1px #000 dotted;border-bottom:1px #000 dotted;">
					[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">At higher perfomance the whole GeoIP will be loaded into memory for the duration of the script.  This might cause a <code>Fatal error: Allowed memory size of XYZ bytes exhausted (tried to allocate 19441809 bytes)</code>, where XYZ depends on your provider settings.  So if you are not allowed to allocate enough memory, try running the memory efficient mode, which will increase rendering times as the database will be read from disk at each call.</font>]</td></tr>
					<tr><td align="left" style="border-bottom:1px #000 dotted;"><input type="radio" name="user_memory_efficient" value="1" '.$memory_efficient.'/> Memory efficient</td></tr>
				</table>
				<p class="submit"><input type="submit" name="submit_buttom" value="'.__('Update Settings only', 'blog.vimaigc.de').'" />&nbsp;&nbsp;&nbsp;<input type="submit" name="recluster" value="'.__('Update Settings & Redraw', 'blog.vimaigc.de').'" /></p>
				</fieldset>
			</form>
			</div>');
		if(intval(get_option('ipccp_recluster'))==1)	{
			print('<div align="center"><table border="0" cellspacing="2" cellpadding="0"><tr><td style="border: 3px solid #AA0000; background-color:#f7f7f7; padding:0.5em;text-align:center;"><strong>Generating the cluster map might take a while - please be patient!</strong></td></tr></table></div><!-- for reference, on my ancient webserver it takes about 15s for 14000 IPs (read from database) and around 330s for about 2.8mill IPs (read from a logfile) -->');
			print('
			<div class="wrap">
				<h2>'.__('&#187; Generating New Cluster Map', 'blog.vimagic.de').'</h2>');
				ob_flush();
				flush();
				$this->_recluster();
			print('</div>');
		}
		print('<div class="wrap"><h2>'.__('&#187; Preview', 'blog.vimagic.de').'</h2>'.$this->_output().'</div>');
		ob_flush();
		flush();
	}
	//////////////////////////////////////////////////////////////////////////////
	// _substitute()  FILTER TOKENS AND SUBSITUTE FOR CORRESPONDING EXIF		//
	//////////////////////////////////////////////////////////////////////////////
	function _substitute($foo)	{		### MAINLY TOKEN HANDLING ###
		$image_html_path_min=trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/ipccp/images/ipccp_out_smal.jpg';
		$image_html_path_max=trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/ipccp/images/ipccp_out_big.jpg';
		$foo = preg_replace('/%IMAGEMAP%/', 'usemap="#IPCCP_MIN"', $foo);
		if($this->user_image_width_min==0)	{$foo = preg_replace('/%WIDTH_MIN%/', '', $foo);}
		else	{
			$width="width=\"$this->user_image_width_min\"";
			$foo = preg_replace('/%WIDTH_MIN%/', "$width", $foo);
		}
		if($this->user_image_width_max==0)	{$foo = preg_replace('/%WIDTH_MAX%/', '', $foo);}
		else	{
			$width="width=\"$this->user_image_width_max\"";
			$foo = preg_replace('/%WIDTH_MAX%/', "$width", $foo);
		}
		if($this->user_show_small_picture)	{$foo = preg_replace('/%IPCCP_MIN%/', $image_html_path_min, $foo);}
		else								{$foo = preg_replace('/%IPCCP_MIN%/', $image_html_path_max, $foo);}
		$foo = preg_replace('/%BLOGNAME%/', get_option('blogname'), $foo);
		$foo = preg_replace('/%IPCCP_MAX%/', $image_html_path_max, $foo);
		return $foo;
	}
	//////////////////////////////////////////////////
	//	GENERATING OUTPUT							//
	//////////////////////////////////////////////////
	function _output	()	{
		add_action('wp_footer', array(&$this, '_wpFoot'));
		$output=$this->user_template_output;
		if ((time() > intval(get_option('ipccp_last_run_time_start'))+intval(get_option('ipccp_user_cron')))){ 	/*||
			((time() > intval(get_option('ipccp_last_run_time_start'))+intval(get_option('ipccp_user_cron'))) &&
			(!empty($_GET['recluster']) && $_GET['recluster'] == "true"))) { 									*/
				$this->_recluster();
		}
		if(!$this->user_use_imagemap)	{$output.='<map name="IPCCP_MIN" id="IPCCP_MIN"><area shape="rect" coords="0,0,'.$this->user_image_width_min.','.($this->factor_of_scaling_min*1000.0).'" href="%IPCCP_MAX%" rel="ipccp" alt ="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Click to zoom" title="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Click to zoom" title_zoomed="Cluster Map for '.get_option('blogname').' @ '.trailingslashit(get_option('home')).' - Hover over the clusters for names, exit by pressing ESC or clicking the upper left icon" /></map>';}
		$output=$this->_substitute($output);
		return ($output);
	}

	//////////////////////////////////////////////////
	//	PROVIDING TOKEN FILTER						//
	//////////////////////////////////////////////////
	function _filter($text) {
		ob_flush();
		flush();
		$text = preg_replace('#\[IPCCP\]#e', "\$this->_output()", $text);
		return $text;
	}
}

$WpIPCCP = new WpIPCCP();
//////////////////////////////////////////////////////
// SOME FUNCTIONS FOR THE CORRECT EVENT HANDLING	//
//////////////////////////////////////////////////////
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
		$bInstall=0;
		foreach ($WpIPCCP->options as $option => $type) {if(get_option('ipccp_'.$option) == '' )	{$bInstall=1;}}
		if($bInstall)	{
			$WpIPCCP->_install();
		}
}
$WpIPCCP->_get_setting();
function ipccp_options() {
	if (function_exists('add_options_page')) {
		add_options_page(
			__('IPCCP Options', 'blog.vimagic.de'),
			__('IPCCP', 'blog.vimagic.de'),
			10,
			basename(__FILE__),
			'ipccp_options_form'
		);
	}
}
if (!empty($_POST['ipccp_action'])) {
	switch($_POST['ipccp_action']) {
		case 'ipccp_update_settings': 
			$WpIPCCP->_update_settings();
			if(!empty($_POST['recluster']) && $_POST['recluster'] == "Update Settings & Redraw")	{update_option('ipccp_recluster', "1");}
			header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=ipccp.php&updated=true');
			die();
	}
}
function ipccp_options_form() {
	global $WpIPCCP;
	$WpIPCCP->_options_form();
}
function ipccp_header() {						###############################
	global $WpIPCCP;							### NEEDED FOR CORRECT		###
	$WpIPCCP->_wpHead();						### PREVIEW IN ADMIN PANEL	###
}												###############################

function ipccp_footer() {						###############################
	global $WpIPCCP;							### NEEDED FOR CORRECT		###
	$WpIPCCP->_wpFoot();						### PREVIEW IN ADMIN PANEL	###
}												###############################

//////////////////////////////////////////////////////////
// SOME HOOKS TO CALL DIFFERENT FUNCTIONS FROM OUTSIDE	//
//////////////////////////////////////////////////////////
function ipccp_recluster() {
	global $WpIPCCP;
	$WpIPCCP->_recluster();	
}

function ipccp() {
	global $WpIPCCP;
	print($WpIPCCP->_output());
}

?>
