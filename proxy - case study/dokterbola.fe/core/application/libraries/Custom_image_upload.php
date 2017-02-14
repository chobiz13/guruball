<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Custom_image_upload {

	private $CI;

	public function __construct(){
		$this->CI =& get_instance();
	}

	public function custom_size($fupload_name,$dir,$img_type,$size){
		//target image directory
		$vdir_upload = $dir;
		$vfile_upload = $vdir_upload . $fupload_name;
		switch ($img_type) {
            case 'jpg':
                    $image_create_func = 'imagecreatefromjpeg';
                    $image_save_func = 'imagejpeg';
                    $new_image_ext = 'jpg';
                    break;
                    
            case 'jpeg':
                    $image_create_func = 'imagecreatefromjpeg';
                    $image_save_func = 'imagejpeg';
                    $new_image_ext = 'jpg';
                    break;

            case 'png':
                    $image_create_func = 'imagecreatefrompng';
                    $image_save_func = 'imagepng';
                    $new_image_ext = 'png';
                    break;

            case 'gif':
                    $image_create_func = 'imagecreatefromgif';
                    $image_save_func = 'imagegif';
                    $new_image_ext = 'gif';
                    break;

            default: 
                    throw new Exception('Unknown image type.');
		}

		foreach ($size as $key => $value) {			

		  	//Original file identity
		  	$im_src = $image_create_func($vfile_upload);
		  	$src_width = imageSX($im_src);
		  	$src_height = imageSY($im_src);

			//Set new image size
			$dst_width = $size[$key];
			$dst_height = ($dst_width/$src_width)*$src_height;

			//Process to modification the size of image
			$im = imagecreatetruecolor($dst_width,$dst_height);
			imagecopyresampled($im, $im_src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);

			//Save Image
			$image_save_func($im,$vdir_upload .$key."_".$fupload_name);  

		}
		imagedestroy($im);

	}
}