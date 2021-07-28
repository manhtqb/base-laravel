<?php

namespace Modules\Common\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Common\Http\Requests\ImageUploadRequest;
use Modules\Common\Services\ImageService;

class ImageController extends ApiController
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(ImageUploadRequest $request)
    {
        $params = $request->all();

        return response()->json([
            'data' => $this->imageService->upload(Config::get('common.file_path_post_editor_image'), $params['image'], $params['method_get_url'] ?? 'path')
        ]);
    }
}
