<?php

namespace AndrewRus\NovaImageCropperField;

use Laravel\Nova\Fields\Image;
use Illuminate\Support\Facades\Storage;

class ImageCropper extends Image
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-image-cropper-field';

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  string|null  $disk
     * @param  callable|null  $storageCallback
     * @return void
     */
    public function __construct($name, $attribute = null, $disk = 'public', $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->preview(function () {
            if (!$this->value) {
                return null;
            }

            $url = Storage::disk($this->disk)->url($this->value);

            $path_info = pathinfo($url);

            $filetype = 'jpg';

            if (array_key_exists('extension', $path_info)) {
                $filetype = $path_info['extension'];
            }

            try {
                $encoded_file = base64_encode(file_get_contents($url));
            } catch (\Exception $e) {
                return '';
            }

            return 'image/' . $filetype . ',,,' . $encoded_file;
        });
    }
}
