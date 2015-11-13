<?php

namespace Izupet\FlyImages;

use Config;
use Cache;

class FlyImages
{
    public function __construct()
    {
        $this->size     = $this->getSize($_COOKIE['resolution']);
        $this->image    = new \Imagick();
    }

    /*
    * Return optimized (cropped or resized) image. If image exists in cache is pulled
    * out, otherwise is generated dynamically and put into cache for next usage.
    *
    * @param string $hash
    *
    * @access public
    * @return object Imagick with proper Content-type header
    */
    public function optimize($hash)
    {
        $queryString    = $_SERVER['QUERY_STRING'];
        $index          = sprintf('%s?%s', $hash, $queryString);
        $height         = $this->getDimension($queryString, 'h');
        $width          = $this->getDimension($queryString, 'w');

        if (Cache::has($index)) {
            $this->image->readImageBlob(Cache::get($index));
        } else {
            $this->image->readImage(sprintf('%s/%s', Config::get('file.folder'), $hash));

            if (isset($height) && $height == $width) {
                $this->crop($width, $height);
            } else if (isset($height, $width) && $height != $width) {
                $this->resize($width, $height);
            }

            Cache::add($index, $this->image->getImageBlob(), Config::get('file.ttl'));
        }

        return response($this->image)->header('Content-type', $this->image->getFormat());
    }

    /*
    * Return resized image according to dimensions. Image ratio is kept.
    *
    * @param int $width
    * @param int $height
    *
    * @access private
    * @return object Imagick
    */
    private function resize($width, $height)
    {
        $this->image->thumbnailImage($width, $height, true);
    }

    /*
    * Return cropped image according to dimensions. Cropped from center.
    *
    * @param int $width
    * @param int $height
    *
    * @access private
    * @return object Imagick
    */
    private function crop($width, $height)
    {
        $this->image->cropThumbnailImage($width, $height);
    }

    /*
    * Get size of the screen according to bootstrap grid
    *
    * @param string $resolution
    *
    * @access private
    * @return string
    */
    private function getSize($resolution)
    {
        switch ($resolution) {
            case ($resolution >= 1200):
                return 'lg';
                break;
            case ($resolution >= 992):
                return 'md';
                break;
            case ($resolution >= 768):
                return 'sm';
                break;
            case ($resolution < 768):
                return 'xs';
                break;
        }
    }

    /*
    * Get size of the screen according to bootstrap grid
    *
    * @param string $queryString
    * @param string $dimension w as width or h as height
    *
    * @access private
    * @return int | null
    */
    private function getDimension($queryString, $dimension)
    {
        parse_str($queryString, $queryParams);

        if ($this->size === 'lg') {
            if (array_key_exists(sprintf('lg-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('lg-%s', $dimension)];
            }
        } else if ($this->size === 'md') {
            if (array_key_exists(sprintf('md-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('md-%s', $dimension)];
            } else if (array_key_exists(sprintf('lg-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('lg-%s', $dimension)];
            }
        } else if ($this->size === 'sm') {
            if (array_key_exists(sprintf('sm-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('sm-%s', $dimension)];
            } else if (array_key_exists(sprintf('md-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('md-%s', $dimension)];
            } else if (array_key_exists(sprintf('lg-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('lg-%s', $dimension)];
            }
        } else if ($this->size === 'xs') {
            if (array_key_exists(sprintf('xs-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('xs-%s', $dimension)];
            } else if (array_key_exists(sprintf('sm-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('sm-%s', $dimension)];
            } else if (array_key_exists(sprintf('md-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('md-%s', $dimension)];
            } else if (array_key_exists(sprintf('lg-%s', $dimension), $queryParams)) {
                return $queryParams[sprintf('lg-%s', $dimension)];
            }
        }
    }
}
