<?php
/**
 * Created by PhpStorm.
 * User: enocryph
 * Date: 16.1.17
 * Time: 21.51
 */

namespace AppBundle\Service;

class ImageDownloader
{
    /**
     * ImageDownloader constructor.
     */
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function downloadImage($image, $oldPath = null)
    {
        if ($image) {
            $imageName = md5(uniqid()).'.'.$image->guessExtension();
            $image->move(
                $this->directory,
                $imageName
            );
            return $imageName;
        } else if ($oldPath) {
            $imageName = $oldPath;
            return $imageName;
        } else {
            $imageName = 'empty.jpg';
            return $imageName;
        }
    }
}