# Image Field with built-in cropper for Laravel Nova

This field extends Image Field adding a handy cropper to manipulate images. Can be configurable in the same way as a [File field in Nova](https://nova.laravel.com/docs/1.0/resources/file-fields.html).

### Install

Run this command into your nova project:
`composer require andrew-rus/nova-image-cropper-field`

### Add it to your Nova Resource:

```php
use AndrewRus\NovaImageCropperField\ImageCropper;

ImageCropper::make('Photo'),
```

### Update form

In order to edit the existing image saved in the model, ImageCroper uses the preview method to return a base64 encoded image. You can either use the default implementation or override it as long as you return a base64 image.

```php
use R64\NovaImageCropper\ImageCropper;

ImageCropper::make('Photo')
        ->preview(function () {
            if (!$this->value) return null;

            $url = Storage::disk($this->disk)->url($this->value);
            $filetype = pathinfo($url)['extension'];
            return 'data:image/' . $filetype . ';base64,' . base64_encode(file_get_contents($url));
        });
```

### Localization

Set your translations in the corresponding xx.json file located in `/resources/lang/vendor/nova`

```php
...

    "Choose Image": "Выбрать изображение",
    "Remove Image": "Удалить изображение",
    "Crop Image": "Обрезать изображение"
```
