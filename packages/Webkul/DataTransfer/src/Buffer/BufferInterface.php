<?php

namespace Webkul\DataTransfer\Buffer;

/**
 * A buffer is an object able to read and write items. It is a simple iterator with an additional write() method.
 * The behavior of the buffer (FIFO / LIFO) and how the items are stocked must be defined by the implementation.
 */
interface BufferInterface
{
    /**
     * @param  mixed  $item  The item to write in the buffer
     * @param  array  $options  The options required by the buffer
     *
     * @throws \InvalidArgumentException If the buffer implementation does not support item of this type
     */
    public function addData($item);
}
