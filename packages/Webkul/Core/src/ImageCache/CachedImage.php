<?php

namespace Webkul\Core\ImageCache;

use Intervention\Image\Interfaces\ImageInterface;

class CachedImage
{
    /**
     * The underlying Intervention Image instance.
     */
    protected ImageInterface $image;

    /**
     * The cache key or checksum for this image.
     */
    protected string $cacheKey;

    /**
     * Create a new CachedImage instance.
     */
    public function __construct(ImageInterface $image, string $cacheKey = '')
    {
        $this->image = $image;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Get the underlying image instance.
     */
    public function getImage(): ImageInterface
    {
        return $this->image;
    }

    /**
     * Get the cache key for this image.
     */
    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    /**
     * Proxy method calls to the underlying image.
     */
    public function __call(string $name, array $arguments): mixed
    {
        $result = call_user_func_array([$this->image, $name], $arguments);

        if ($result instanceof ImageInterface) {
            $this->image = $result;

            return $this;
        }

        return $result;
    }

    /**
     * Convert to encoded string.
     */
    public function __toString(): string
    {
        return (string) $this->image->encodeByMediaType();
    }
}
