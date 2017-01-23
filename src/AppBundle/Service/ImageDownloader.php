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
    private $emptyImage;


    public function __construct($directory, $emptyImage)
    {
        $this->directory = $directory;
        $this->emptyImage = $emptyImage;
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
            $imageName = $this->emptyImage;
            return $imageName;
        }
    }
}