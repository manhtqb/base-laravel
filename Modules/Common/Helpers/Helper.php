<?php

namespace Modules\Common\Helpers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Log;
use Exception;
use Illuminate\Support\Facades\Storage;

class Helper
{
    public static function csvToArray($header_csv, $filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = [];
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (!$header && array(null) === $row) {
                    // if blank header
                    throw new Exception(trans('common.import.no_header'), 696);
                }
                // Check blank line
                if (array(null) !== $row) {
                    $new_data = array();
                    foreach ($row as $value) {
                        $new_data[] = trim(mb_convert_encoding($value, "UTF-8", "cp932"));
                    }
                    if (!$header) {
                        if ($new_data !== $header_csv)
                            throw new Exception(trans('common.import.header_not_match'), 696);
                        else
                            $header = $new_data;
                    } else {
                        try {
                            $data[] = array_combine($header, $new_data);
                        } catch (Exception $e) {
                            Log::error('Import error - name: ' . $filename . ' - row: ' . (count($data) + 2));
                            continue;
                        }
                        if (count($data) > config('common.max_row_import_csv')) {
                            throw new Exception(trans('common.import.max_row'), 696);
                        }
                    }
                } else {
                    Log::error('Import error - name: ' . $filename . ' - row: ' . (count($data) + 2));
                    continue;
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public static function getListDayOfMonth($date)
    {
        $start = Carbon::parse($date)->startOfMonth();
        $end = Carbon::parse($date)->endOfMonth();

        $dates = [];
        while ($start->lte($end)) {
            $dates[] = $start->copy();
            $start->addDay();
        }

        return $dates;
    }

    public static function sortAscArrayByField($array, $field)
    {
        usort($array, function ($a, $b) use ($field) {
            return strnatcmp($a[$field], $b[$field]);
        });

        return $array;
    }

    public static function makePagination($queryCount, $queryPaginate, $itemPerPage, $page)
    {
        /* Get total */
        $totalCount = $queryCount->count();

        /* Get data */
        $slice = $page ? $itemPerPage * ($page - 1) : 0;
        $data = $queryPaginate->slice($slice, $itemPerPage)->toArray();

        return new \Illuminate\Pagination\LengthAwarePaginator(array_values($data), $totalCount, $itemPerPage);
    }

    public static function checkSpecialCharacter($valueSearch)
    {
        $valueSearch = strpos($valueSearch, '%') !== false ? preg_replace('/\%/', '\%', $valueSearch) : $valueSearch;
        $valueSearch = strpos($valueSearch, '_') !== false ? preg_replace('/\_/', '\_', $valueSearch) : $valueSearch;
        $valueSearch = preg_match('/\\\/', $valueSearch) !== false ? addslashes($valueSearch) : $valueSearch;

        return $valueSearch;
    }

    /**
     * @param string $filePath
     * @param UploadedFile $file
     * @return bool|string
     */
    public static function uploadFile(string $filePath, UploadedFile $file, bool $includeTime = true, bool $replaceName = true, string $prefix = '')
    {
        try {
            $disk = config('filesystems.default');
            $storage = Storage::disk($disk);
            $fileName = $file->getClientOriginalName();
            $fileExt = $file->getClientOriginalExtension();
            if ($includeTime) {
                $fileName = time() . '_' . $fileName;
                if ($replaceName) {
                    $fileName = $prefix . time() . '.' . $fileExt;
                }
            }

            $storage->putFileAs($filePath, $file, $fileName);

            $filePath = $filePath . '/' . $fileName;
        } catch (\Exception $exception) {
            \Log::error($exception);
            return false;
        }

        return $filePath;
    }

    /**
     * @param file or base64 $file
     * @param string $businessCode : business_code
     * @param string $folder : table_name
     * @param bool $includeTime : include time string to file name
     * @param null $file_name
     * @return string : Uploaded success or not
     */
    public static function uploadFileToS3($file, string $folder = '', string $file_name = null, bool $includeTime = false, $includeThumbnail = false)
    {
        if (self::checkImageBase64($file)) {
            $content = self::getContentBase64($file);
            $fileExtension = explode('/', mime_content_type($file))[1];
        } else {
            $fileExtension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            $content = file_get_contents($file);
        }
        $fileName = $file_name ?? Str::uuid()->toString() . '_' . $folder . '.' . $fileExtension;
        if ($includeTime) {
            $fileName = time() . '_' . $fileName;
        }
        $thumbnailFile = null;
        // resize image as thumbnail
        if ($includeThumbnail) {
            $thumbnailFile = self::resizeImage($file, $fileExtension, config('aws.thumbnail_width'), config('aws.thumbnail_height'), 100);
            $thumbnailFileName = config('aws.thumbnail_prefix') . '/' . $fileName;
        }
        $options = $fileExtension == 'svg' ? ['visibility' => 'public', 'ContentType' => 'image/svg+xml'] : 'public';

        $prefix = config('aws.s3_root_prefix') ? '/' . config('aws.s3_root_prefix') : '';

        $folderPath = $folder ? '/' . $folder : '';
        $filePath = $prefix . $folderPath . '/' . $fileName;

        $s3 = Storage::disk('s3');

        if (!$s3->put($filePath, $content, $options)) {
            throw new Exception("Upload file fail!");
        }
        // upload thumbnail
        if ($thumbnailFile) {
            $thumbnailFilePath = $folderPath . '/' . $thumbnailFileName;
            if (!$s3->put($thumbnailFilePath, $thumbnailFile, $options)) {
                throw new Exception("Upload file fail!");
            }
        }

        return $fileName;
    }

    /**
     * @param string $base64_string
     * @return bool|string
     */
    protected static function getContentBase64(string $base64_string)
    {
        if (!self::checkImageBase64($base64_string)) return false;
        $data = explode(',', $base64_string);

        return base64_decode($data[1]);
    }

    /*
 * @param string
 * @return bool
 */
    protected static function checkImageBase64($image)
    {
        return (substr($image, 0, 11) == 'data:image/') ? true : false;
    }

    public static function resizeImage($file, $fileExtension, $maxWidth, $maxHeight, $quality = null)
    {
        if (is_resource($file)) {
            $src_img = $file;
        } elseif (gettype($file) == 'string') {
            $content = self::getContentBase64($file);
            $src_img = imagecreatefromstring($content);
        } else {
            try {
                switch ($fileExtension) {
                    case 'png':
                        $src_img = imagecreatefrompng($file);
                        break;
                    case 'gif':
                        $src_img = imagecreatefromgif($file);
                        break;
                    default:
                        $src_img = imagecreatefromjpeg($file);
                        break;
                }
            } catch (Exception $e) {
                $src_img = imagecreatefromjpeg($file);
            }

        }

        // Get dimensions of source image.

        $origWidth = imageSX($src_img);
        $origHeight = imageSY($src_img);
        //list($origWidth, $origHeight) = getimagesize($file);

        if ($maxWidth == 0) {
            $maxWidth = $origWidth;
        }

        if ($maxHeight == 0) {
            $maxHeight = $origHeight;
        }

        if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
            //do not resize
            $ratio = 1;
        } else {
            // Calculate ratio of desired maximum sizes and original sizes.
            $widthRatio = $maxWidth / $origWidth;
            $heightRatio = $maxHeight / $origHeight;

            // Ratio used for calculating new image dimensions.
            $ratio = min($widthRatio, $heightRatio);
        }


        // Calculate new image dimensions.
        $newWidth = (int)$origWidth * $ratio;
        $newHeight = (int)$origHeight * $ratio;

        // Create final image with new dimensions.
        $dst_img = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        $dst_img = self::imageFixOrientation($dst_img, $file, $fileExtension);

        ob_start();
        switch ($fileExtension) {
            case 'png':
                imagepng($dst_img, null, $quality ? floor($quality / 10) - 1 : config('aws.image_compress_png'));
                break;
            case 'gif':
                imagegif($dst_img, null);
                break;
            default:
                imagejpeg($dst_img, null, $quality ? $quality : config('aws.image_compress_jpg'));
                break;
        }
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public static function correctImageOrientation($file)
    {
        $exif = @exif_read_data($file);
        if ($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];

            if ($orientation != 1) {
//                switch ($orientation) {
//                    case 3:
//                        $deg = 180;
//                        break;
//                    case 6:
//                        $deg = -90;
//                        break;
//                    case 8:
//                        $deg = 90;
//                        break;
//                }
                $flip = 0;
                $deg = 0;
                switch ($orientation) {
                    case 2:
                        $flip = 1;
                        $deg = 0;
                        break;
                    case 3:
                        $flip = 0;
                        $deg = 180;
                        break;
                    case 4:
                        $flip = 2;
                        $deg = 0;
                        break;
                    case 5:
                        $flip = 2;
                        $deg = -90;
                        break;
                    case 6:
                        $flip = 0;
                        $deg = -90;
                        break;
                    case 7:
                        $flip = 1;
                        $deg = -90;
                        break;
                    case 8:
                        $flip = 0;
                        $deg = 90;
                        break;
                    default:
                        $flip = 0;
                        $deg = 0;
                }

                if ($deg !== 0 || $flip !== 0) {

                    $img = imagecreatefromjpeg($file);

                    if ($flip !== 0) {
                        imageflip($img, $flip);
                    }

                    if ($deg !== 0) {
                        $img = imagerotate($img, $deg, 0);
                    }

                    ob_start();
                    imagejpeg($img, null, 100);
                    $content = ob_get_contents();
                    ob_end_clean();

                    return $content;
                }

                // then rewrite the rotated image back to the disk as $file


            } // if there is some rotation necessary
        } // if have the exif orientation info

        return null;
    }

    public static function convertYoutubeUrlToEmbedUrl(String $url)
    {
        $result = preg_replace(
            "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
            "https://www.youtube.com/embed/$2",
            $url
        );

        return $result;
    }

    public static function generateSlug($model, $title, $id = null)
    {
        if (!$model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'slug')) {
            throw new Exception('The model does not have slug column');
        }

        $slug = Str::slug($title);
        $isExists = $id ?
            $model->where('id', '<>', $id)->whereSlug($slug)->exists() :
            $model->whereSlug($slug)->exists();
        if ($isExists) {
            $slug .= '-' . time();
        }

        return $slug;
    }

    public static function getFullUrlFile(string $filePath)
    {
        $disk = config('filesystems.disk_driver');
        $storage = Storage::disk($disk);

        return $storage->path($filePath);
    }

    public static function getS3FullUrlFile(string $filePath)
    {
        $disk = Storage::disk('s3');
        $client = $disk->getDriver()->getAdapter()->getClient();

        return $client->getObjectUrl(config('filesystems.disks.s3.bucket'), $filePath);
    }
}
