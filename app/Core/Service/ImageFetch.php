<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 5/13/16
 * Time: 12:22 PM
 */

namespace App\Core\Service;


use App\Core\Models\OrderCore\ImportListing\Image;
use App\Core\Models\OrderCore\JobQueue;
use App\Core\Models\OrderCore\Listing;
use App\Core\Models\OrderCore\Log;
use App\Core\Utility\FilePath;
use Illuminate\Http\File;

class ImageFetch
{

    public static function process(Listing $listing, Image $image, $isMainImage = false)
    {
        $_logger = app()->make(Log::class);
        $_appName = config('app.name');
        $config = config('app.server_config');
        $tempPath = FilePath::calculatePath();
        $userPath = $config['designServer']['userPath'] . DIRECTORY_SEPARATOR . $tempPath;

        if (!file_exists($userPath)) {
            mkdir($userPath, 0755, true);
        }

        // get order_core -> user
        $_user = $listing->user;

        // get image and save to new path
        $originalFileName = $image->image_sort . '.jpg';
        file_put_contents($userPath . DIRECTORY_SEPARATOR . $originalFileName, file_get_contents($image->image_path));
        $fileExtension = pathinfo($userPath . DIRECTORY_SEPARATOR . $originalFileName, PATHINFO_EXTENSION);
        $mimeType = (new File($userPath . DIRECTORY_SEPARATOR . $originalFileName))->getMimeType();
        // Thumb
        $uniquid = uniqid();
        $thumbName = 'thumb-' . $uniquid . '.jpg';
        $thumbPath = $userPath . DIRECTORY_SEPARATOR . $thumbName;
        // Image File
        $fileName = 'uploadimage-' . $uniquid . '.' . $fileExtension;
        $filePath = $userPath . DIRECTORY_SEPARATOR . $fileName;

        //rename uploaded file
        rename($userPath . DIRECTORY_SEPARATOR . $originalFileName, $filePath);

        list($width, $height) = getimagesize($filePath);

        try {
            $jobQueue = new JobQueue();
            $jobQueue->mime_type = $mimeType;
            $jobQueue->task = 'profile-conversion';
            $jobQueue->data = serialize(array('filePath' => $filePath));
            $jobQueue->save();

            // get/wait for return value from processing server
            $jobQueue->getJobResult($config['jobQueueTimeout']);
            if ('error' == $jobQueue->status) {
                throw new \Exception('Error with image processing');
            }
        } catch (\Exception $e) {
            $_logger->logError(
                $_appName,
                'Could not upload user image. Original error: ' . $e->getMessage()
            );

            return response()->json(
                [
                    'status' => 'error',
                    'msg'    => 'Could not upload user image'
                ], 500
            );
        }

        try {
            // thumbnails
            $jobQueue2 = new JobQueue();
            $jobQueue2->mime_type = $mimeType;
            $jobQueue2->task = 'thumb-generation';
            $jobQueue2->data = serialize(
                array(
                    'filePath'  => $filePath,
                    'thumbPath' => $thumbPath,
                    'thumbSize' => $config['designFile']['largeThumbnailDimensions']
                )
            );
            $jobQueue2->save();

            // get/wait for return value from processing server
            $jobQueue2->getJobResult($config['jobQueueTimeout']);
            if ('error' == $jobQueue2->status) {
                throw new \Exception('Error with thumbnail generation');
            }
        } catch (\Exception $e) {
            $_logger->logError(
                $_appName,
                'Could not upload user image. Original error: ' . $e->getMessage()
            );

            return response()->json(
                [
                    'status' => 'error',
                    'msg'    => 'Could not upload user image'
                ], 500
            );
        }

        try {
            $images = new \App\Core\Models\EZT2\User\Image();
            $images->filename = $fileName;
            $tempPathWin = str_replace('/', '\\', $tempPath);
            $images->filepath = $config['designServer']['windowsUserPath'] . '\\' . $tempPathWin;
            $images->uri = $config['designServer']['webPath'] . DIRECTORY_SEPARATOR . $tempPath . DIRECTORY_SEPARATOR . $fileName;
            $images->file_label = $originalFileName;
            $images->description = '';
            $images->image_type = 10;// listing image
            $images->thumbnail = $config['designServer']['webPath'] . DIRECTORY_SEPARATOR . $tempPath . DIRECTORY_SEPARATOR . $thumbName;
            $images->display_file = $config['designServer']['webPath'] . DIRECTORY_SEPARATOR . $tempPath . DIRECTORY_SEPARATOR . $thumbName;
            $images->date_added = date('Y-m-d h:i:s');
            $images->library_flag = '0';
            $images->token = 'if' . $uniquid;
            $images->width = $width;
            $images->height = $height;
            $newImage = $_user->images()->save($images);

            if ($isMainImage) {
                $old = $listing->mainImage;
                //Do I know you?
                if ($old) {
                    //I don't like you anymore
                    $old->update(
                        [
                            'main_image' => 0
                        ]
                    );
                }
            }
            if (!$listingImage = $listing->images()->where('image_id', $newImage->image_id)->first()) {
                    //Never heard of it, create a new one
                    Listing\Image::create(
                        [
                            'listing_id' => $listing->id,
                            'image_id'   => $newImage->image_id,
                            'main_image' => ($isMainImage ? 1 : 0)
                        ]
                    );
            }
        } catch (\Exception $e) {
            $_logger->logError(
                $_appName,
                'Could not upload user image. Original error: ' . $e->getMessage()
            );
        }
        return true;
    }
}