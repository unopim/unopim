{{--
    The page's Vue app is already mounted when this fragment arrives, so it is
    mounted by its own instance. That instance only knows the components this
    fragment registers itself, hence the stack is yielded here rather than left
    to the layout.
--}}
@include('admin::catalog.products.edit.attribute-group-panel', [
    'product'            => $product,
    'group'              => $group,
    'customAttributes'   => $customAttributes,
    'variantHiddenCodes' => $variantHiddenCodes,
])

@stack('scripts')
