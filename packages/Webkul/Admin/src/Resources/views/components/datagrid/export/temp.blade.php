<table>
    <thead>
        <tr>
            @foreach ($columns as $key => $value)
                <th>{{ $value == 'increment_id' ? 'order_id' : $value }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach ($records as $record)
            @php $record = (array) $record; @endphp
            <tr>
                @foreach($columns as $columnName)
                    <td>{{ isset($record[$columnName]) ? $record[$columnName] : '' }} </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
