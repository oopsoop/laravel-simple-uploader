<?php

namespace Rymanalu\LaravelSimpleUploader;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Str;
use Rymanalu\LaravelSimpleUploader\Contracts\Provider;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Rymanalu\LaravelSimpleUploader\Contracts\Uploader as UploaderContract;

class Uploader implements UploaderContract
{
    /**
     * The file storage where the file will be uploaded.
     *
     * @var string
     */
    public $disk;

    /**
     * The name of file.
     *
     * @var string
     */
    public $filename;

    /**
     * The file visibility.
     *
     * @var string|null
     */
    public $visibility;

    /**
     * The folder where the file will be stored.
     *
     * @var string
     */
    public $folder = '';

    /**
     * The file provider implementation.
     *
     * @var \Rymanalu\LaravelSimpleUploader\Contracts\Provider
     */
    protected $provider;

    /**
     * The FilesystemManager implementation.
     *
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $filesystem;

    /**
     * Create a new Uploader instance.
     *
     * @param  \Illuminate\Contracts\Filesystem\Factory  $filesystem
     * @param  \Rymanalu\LaravelSimpleUploader\Contracts\Provider  $provider
     * @return void
     */
    public function __construct(FilesystemManager $filesystem, Provider $provider)
    {
        $this->provider = $provider;

        $this->filesystem = $filesystem;
    }

    /**
     * Specify the file storage where the file will be uploaded.
     *
     * @param  string  $disk
     * @return \Rymanalu\LaravelSimpleUploader\Contracts\Uploader
     */
    public function uploadTo($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Specify the folder where the file will be stored.
     *
     * @param  string  $folder
     * @return \Rymanalu\LaravelSimpleUploader\Contracts\Uploader
     */
    public function toFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Rename the uploaded file to given new name.
     *
     * @param  string  $newName
     * @return \Rymanalu\LaravelSimpleUploader\Contracts\Uploader
     */
    public function renameTo($newName)
    {
        $this->filename = $newName;

        return $this;
    }

    /**
     * Set the visibility of the file.
     *
     * @param  string  $visibility
     * @return \Rymanalu\LaravelSimpleUploader\Contracts\Uploader
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Upload the file on a file storage.
     *
     * @param  string  $file
     * @param  \Closure|null $callback
     * @return bool
     */
    public function upload($file, Closure $callback = null)
    {
        $uploadedFile = $this->runUpload($file);

        if (! $uploadedFile) {
            return false;
        }

        if ($callback) {
            $callback($uploadedFile);
        }

        return true;
    }

    /**
     * Upload the given file and returns the filename if succeed.
     *
     * @param  string  $file
     * @return string|bool
     *
     * @throws \RuntimeException
     */
    protected function runUpload($file)
    {
        $this->provider->setFile($file);

        if (! $this->provider->isValid()) {
            throw new RuntimeException("Given file [{$file}] is not valid.");
        }

        $filename = $this->getFullFileName($this->provider);

        if ($this->filesystem->disk($this->disk)->put($filename, $this->provider->getContents(), $this->visibility)) {
            return $filename;
        }

        return false;
    }

    /**
     * Get the full filename.
     *
     * @param  \Rymanalu\LaravelSimpleUploader\Contracts\Provider  $provider
     * @return string
     */
    protected function getFullFileName(Provider $provider)
    {
        $folder = $this->folder ? rtrim($this->folder, '/').'/' : '';

        if ($this->filename) {
            $filename = $this->filename;
        } else {
            $filename = md5(uniqid(microtime(true), true));
        }

        return $folder.$filename.'.'.$provider->getExtension();
    }

    /**
     * Handle dynamic "uploadTo" method calls.
     *
     * @param  string  $uploadTo
     * @return \Rymanalu\LaravelSimpleUploader\Contracts\Uploader
     */
    protected function dynamicUploadTo($uploadTo)
    {
        $disk = Str::snake(substr($uploadTo, 8));

        return $this->uploadTo($disk);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'uploadTo')) {
            return $this->dynamicUploadTo($method);
        }

        $className = static::class;

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}