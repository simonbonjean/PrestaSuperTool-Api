<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 24/07/13
 * Time: 17:47
 *
 * Exemple of use
 * {imageResize path='c' name='3.jpg' height=15 width=15}
 */

ini_set('memory_limit', '1024M');

class PstImage extends ImageManager{
    const METHOD_RESIZE = 1;
    const METHOD_CROP = 2;
    public static function pathCleaner($path)
    {
        return str_replace('\\','/', $path);
    }
    public static function cacheDirCustomPath($path, $height, $width, $method){
        return $path.'cache' .DS.$height .'x'. $width .'-'.$method.DS;
    }
    public static function cacheResizeCustomPath($path, $name, $height, $width, $method=self::METHOD_RESIZE){
        $dst_path = self::cacheDirCustomPath($path, $height, $width, $method);
        $dst_file = $dst_path . $name;
        $src_file = $path . DS . $name;
        if(!file_exists(_PS_ROOT_DIR_ . $dst_path.$name))
        {
            if(!is_dir(_PS_ROOT_DIR_ . $dst_path))
                mkdir(_PS_ROOT_DIR_ . $dst_path, 0777, true);

            if($method == self::METHOD_RESIZE)
            {
                if(self::resize(_PS_ROOT_DIR_. $src_file, _PS_ROOT_DIR_. $dst_file, $width, $height, $file_type = 'jpg', $force_type = false))
                    return self::pathCleaner($dst_path . $name);
            }
            elseif($method == self::METHOD_CROP)
            {
                if(self::crop(_PS_ROOT_DIR_. $src_file, _PS_ROOT_DIR_. $dst_file, $width, $height, $file_type = 'jpg', $force_type = false))
                    return self::pathCleaner($dst_path . $name);
            }
        }
        else{
            return self::pathCleaner($dst_path . $name);
        }

        return false;
    }


    public static function cacheDir($path, $height, $width, $method){
        return 'cache'.DS.$path .DS.$height .'x'. $width .'-'.$method.DS;
    }
    public static function cacheResize($path, $name, $height, $width, $method=self::METHOD_RESIZE){
        $dst_path = self::cacheDir($path, $height, $width, $method);
        $dst_file = $dst_path . $name;
        $src_file = _PS_IMG_DIR_ . $path . DS . $name;

        if(!file_exists(_PS_IMG_DIR_.$dst_path.$name))
        {
            if(!is_dir(_PS_IMG_DIR_.$dst_path))
                mkdir(_PS_IMG_DIR_.$dst_path, 0777, true);

            if($method == self::METHOD_RESIZE)
            {   if(self::resize($src_file, _PS_IMG_DIR_. $dst_file, $width, $height, $file_type = 'jpg', $force_type = false))
                    return self::pathCleaner(_PS_IMG_ . $dst_path . $name);
            }
            elseif($method == self::METHOD_CROP)
            {
                if(self::crop($src_file, _PS_IMG_DIR_. $dst_file, $width, $height, $file_type = 'jpg', $force_type = false))
                    return self::pathCleaner(_PS_IMG_. $dst_path . $name);
            }
        }
        else{
            return self::pathCleaner(_PS_IMG_ . $dst_path . $name);
        }

        return false;
    }


    /**
     * Resize, cut and optimize image
     *
     * Zoom & Croop when the destination file name
     * contains the '_timthumb' phrase
     * - Modified by www.bazingadesigns.com/en
     * (TimThumb ZoomCrop port)
     *
     * @param string $src_file Image object from $_FILE
     * @param string $dst_file Destination filename
     * @param integer $dst_width Desired width (optional)
     * @param integer $dst_height Desired height (optional)
     * @param string $file_type
     * @return boolean Operation result
     */
    public static function crop($src_file, $dst_file, $dst_width = null, $dst_height = null, $file_type = 'jpg', $force_type = false)
    {
        if (!file_exists($src_file))
            return false;
        list($src_width, $src_height, $type) = getimagesize($src_file);

        // If PS_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
        // This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
        // because JPG reencoding by GD, even with max quality setting, degrades the image.
        if (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == IMAGETYPE_PNG) && !$force_type)
            $file_type = 'png';


        if (!$src_width)
            return false;

            if (!$dst_width) $dst_width = 0;
            if (!$dst_height) $dst_height = 0;



        $src_image = ImageManager::create($type, $src_file);

        $width_diff = $dst_width / $src_width;
        $height_diff = $dst_height / $src_height;


            if ($dst_width>0 && $dst_height<1) {
                $dst_height = floor ($src_height * ($dst_width / $src_width));
            } else if ($dst_height>0 && $dst_width<1) {
                $dst_width = floor ($src_width * ($dst_height / $src_height));
            }

            $src_x = $src_y = 0;
            $src_w = $src_width;
            $src_h = $src_height;

            $cmp_x = $src_width / $dst_width;
            $cmp_y = $src_height / $dst_height;

            if ($cmp_x > $cmp_y) {

                $src_w = round (($src_width / $cmp_x * $cmp_y));
                $src_x = round (($src_width - ($src_width / $cmp_x * $cmp_y)) / 2);

            } else if ($cmp_y > $cmp_x) {

                $src_h = round (($src_height / $cmp_y * $cmp_x));
                $src_y = round (($src_height - ($src_height / $cmp_y * $cmp_x)) / 2);

            }


        $dest_image = imagecreatetruecolor($dst_width, $dst_height);

        // If image is a PNG and the output is PNG, fill with transparency. Else fill with white background.
        if ($file_type == 'png' && $type == IMAGETYPE_PNG)
        {
            imagealphablending($dest_image, false);
            imagesavealpha($dest_image, true);
            $transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
            imagefilledrectangle($dest_image, 0, 0, $dst_width, $dst_height, $transparent);
        }
        else
        {
            $white = imagecolorallocate($dest_image, 255, 255, 255);
            imagefilledrectangle ($dest_image, 0, 0, $dst_width, $dst_height, $white);
        }

        imagecopyresampled($dest_image, $src_image, 0, 0, $src_x, $src_y, $dst_width, $dst_height, $src_w, $src_h);

        return (ImageManager::write($file_type, $dest_image, $dst_file));
    }

}