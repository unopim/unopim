@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];

    $name = $coreConfigRepository->getNameField($nameKey);

    $value = $coreConfigRepository->getValueByRepository($field);

    $validations = $coreConfigRepository->getValidations($field);

    $isRequired = Str::contains($validations, 'required') ? 'required' : '';

    $channelLocaleInfo = $coreConfigRepository->getChannelLocaleInfo($field, $currentChannel->code, $currentLocale->code);
@endphp

<input type="hidden" name="keys[]" value="{{ json_encode($item) }}">

<x-admin::form.control-group>
    @if (! empty($field['depends']))
        @include('admin::configuration.dependent-field-type')
    @else
        <!-- Title of the input field -->
        <div class="flex justify-between">
            <x-admin::form.control-group.label
                :for="$name"
            >
                {!! __($field['title']) . ( __($field['title']) ? '<span class="'.$isRequired.'"></span>' : '') !!}
            </x-admin::form.control-group.label>
        </div>

        <!-- Text input -->
        @if ($field['type'] == 'text')
            <x-admin::form.control-group.control
                type="text"
                :id="$name"
                :name="$name"
                :value="old($nameKey) ?? (core()->getConfigData($nameKey) ? core()->getConfigData($nameKey) : ($field['default_value'] ?? ''))"
                :rules="$validations"
                :label="trans($field['title'])"
            />

        <!-- Password input -->
        @elseif ($field['type'] == 'password')
            <x-admin::form.control-group.control
                type="password"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :value="old($nameKey) ?? core()->getConfigData($nameKey)"
                :label="trans($field['title'])"
            />

        <!-- Number input -->
        @elseif ($field['type'] == 'number')
            <x-admin::form.control-group.control
                type="number"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :value="old($nameKey) ?? core()->getConfigData($nameKey)"
                :label="trans($field['title'])"
                :min="$field['name'] == 'minimum_order_amount'"
            />

        <!-- Textarea Input -->
        @elseif ($field['type'] == 'textarea')
            <x-admin::form.control-group.control
                type="textarea"
                class="text-gray-600 dark:text-gray-300"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :value="old($nameKey) ?: core()->getConfigData($nameKey) ?: (isset($field['default_value']) ? $field['default_value'] : '')"
                :label="trans($field['title'])"
            />

        <!-- Textarea Input -->
        @elseif ($field['type'] == 'editor')
            <!-- (@suraj-webkul) TODO Change textarea to tiny mce -->
            <x-admin::form.control-group.control
                type="textarea"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :value="old($nameKey) ?: core()->getConfigData($nameKey) ?: (isset($field['default_value']) ? $field['default_value'] : '')"
                :label="trans($field['title'])"
            />

        <!-- Select input -->
        @elseif ($field['type'] == 'select')
            @php $selectedOption = core()->getConfigData($nameKey) ?? ''; @endphp

            <x-admin::form.control-group.control
                type="select"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :value="$selectedOption"
                :label="trans($field['title'])"
            >
                @if (isset($field['repository']))
                    @foreach ($value as $key => $option)
                        <option
                            value="{{ $key }}"
                            {{ $key == $selectedOption ? 'selected' : ''}}
                        >
                            @lang($option)
                        </option>
                    @endforeach
                @else
                    @foreach ($field['options'] as $option)
                        <option
                            value="{{ $option['value'] ?? 0 }}"
                            {{ $value == $selectedOption ? 'selected' : ''}}
                        >
                            @lang($option['title'])
                        </option>
                    @endforeach
                @endif
            </x-admin::form.control-group.control>

        <!-- Multiselect Input -->
        @elseif ($field['type'] == 'multiselect')
            @php $selectedOption = core()->getConfigData($nameKey) ?? ''; @endphp

            <v-field
                name="{{ $name }}[]"
                id="{{ $name }}"
                rules="{{ $validations }}"
                label="{{ trans($field['title']) }}"
                multiple
            >
                <select
                    name="{{ $name }}[]"
                    class="flex w-full min-h-[39px] py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-800"
                    :class="[errors['{{ $name }}[]'] ? 'border border-red-600 hover:border-red-600' : '']"
                    multiple
                >
                    @if (isset($field['repository']))
                        @foreach ($value as $key => $option)
                            <option 
                                value="{{ $key }}"
                                {{ in_array($key, explode(',', $selectedOption)) ? 'selected' : ''}}
                            >
                                {{ trans($value[$key]) }}
                            </option>
                        @endforeach
                    @else
                        @foreach ($field['options'] as $option)
                            <option 
                                value="{{ $value = $option['value'] ?? 0 }}"
                                {{ in_array($value, explode(',', $selectedOption)) ? 'selected' : ''}}
                            >
                                @lang($option['title'])
                            </option>
                         @endforeach
                    @endif
                </select>
            </v-field>


        <!-- Boolean/Switch input -->
        @elseif ($field['type'] == 'boolean')
            @php
                $selectedOption = core()->getConfigData($nameKey) ?? ($field['default_value'] ?? '');
            @endphp

            <input type="hidden" name="{{ $name }}" value="0" />

            <label class="relative inline-flex items-center cursor-pointer">
                <input  
                    type="checkbox"
                    name="{{ $name }}"
                    value="1"
                    id="{{ $name }}"
                    class="sr-only peer"
                    {{ $selectedOption ? 'checked' : '' }}
                >

                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-700"></div>
            </label>

        @elseif ($field['type'] == 'image')

            @php
                $src = Storage::url(core()->getConfigData($nameKey));
                $result = core()->getConfigData($nameKey);
            @endphp

            <div class="flex justify-center items-center">
                @if ($result)
                    <a
                        href="{{ $src }}"
                        target="_blank"
                    >
                        <img
                            src="{{ $src }}"
                            class="relative mr-5 h-[33px] w-[33px] top-15 rounded-3 border-3 border-gray-500"
                        />
                    </a>
                @endif

                <x-admin::form.control-group.control
                    type="file"
                    :id="$name"
                    :name="$name"
                    :rules="$validations"
                    :label="trans($field['title'])"
                />
            </div>

            @if ($result)
                <x-admin::form.control-group class="flex gap-1.5 items-center w-max mt-1.5 cursor-pointer select-none">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        class="peer"
                        :id="$name.'[delete]'"
                        :name="$name.'[delete]'"
                        value="1"
                        :for="$name.'[delete]'"
                    />

                    <label
                        for="{{ $name }}[delete]"
                        class="!text-sm !font-semibold !text-gray-600 dark:!text-gray-300 cursor-pointer"
                    >
                        @lang('admin::app.configuration.index.delete')
                    </label>
                </x-admin::form.control-group>
            @endif

        @elseif ($field['type'] == 'file')
            @php
                $result = core()->getConfigData($nameKey);
                $src = explode("/", $result);
                $path = end($src);
            @endphp

            @if ($result)
                <a
                    href="{{ route('admin.configuration.download', [request()->route('slug'), request()->route('slug2'), $path]) }}"
                >
                    <i class="icon sort-down-icon download"></i>
                </a>
            @endif

            <x-admin::form.control-group.control
                type="file"
                :id="$name"
                :name="$name"
                :rules="$validations"
                :label="trans($field['title'])"
            />

            @if ($result)
                <div class="flex gap-2.5 cursor-pointer">
                    <x-admin::form.control-group.control
                        type="checkbox"
                        class="peer"
                        :id="$name.'[delete]'"
                        :name="$name.'[delete]'"
                        value="1"
                    />

                    <label
                        class="cursor-pointer"
                        for="{{ $name }}[delete]'"
                    >
                        @lang('admin::app.configuration.index.delete')
                    </label>
                </div>
            @endif
        @endif
 

        @if (isset($field['info']))
            <label
                class="block leading-5 text-xs text-gray-600 dark:text-gray-300 font-medium"
                for="{{ $name }}-info"
            >
                {!! trans($field['info']) !!}
            </label>
        @endif

        <!-- Input field validaitons error message -->
        <x-admin::form.control-group.error
            :control-name="$name"
        >
        </x-admin::form.control-group.error>
    @endif
</x-admin::form.control-group>
 
