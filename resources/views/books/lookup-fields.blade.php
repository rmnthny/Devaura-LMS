<div class="row g-2">
    @foreach ($fields as $field => $label)
        <div class="col-md">
            <label class="form-label" for="{{ $prefix }}-{{ $field }}">{{ $label }}</label>
            @if ($field === 'publisher_type')
                <select class="form-select" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" data-publisher-type="{{ $prefix }}">
                    <option value="">Select a type</option>
                    @foreach (['Individual', 'Organization'] as $type)
                        <option @selected($item?->getAttribute($field) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            @elseif ($field === 'genre_name')
                <input class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" value="{{ $item?->getAttribute($field) }}"
                       maxlength="100" pattern="{{ \App\Http\Requests\SaveCatalogLookupRequest::GENRE_PATTERN }}"
                       title="Use letters, spaces, hyphens, and & only." placeholder="e.g. Non-Fiction"
                       oninput="this.value = this.value.replace(/[^\p{L}\p{M} \-&]/gu, '')"
                       onblur="this.value = this.value.trim().replace(/ +/g, ' ')" required>
            @elseif ($field === 'last_name' || $field === 'first_name')
                <input class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" value="{{ $item?->getAttribute($field) }}"
                       maxlength="50" pattern="{{ \App\Support\MemberFieldRules::NAME_PATTERN }}"
                       title="Use letters and spaces only." placeholder="{{ $field === 'last_name' ? 'Dela Cruz' : 'Juan' }}"
                       oninput="this.value = this.value.replace(/[^\p{L}\p{M} ]/gu, '')"
                       onblur="this.value = this.value.trim().replace(/ +/g, ' ')">
            @elseif ($field === 'middle_initial')
                <input class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" value="{{ $item?->getAttribute($field) }}"
                       maxlength="5" pattern="{{ \App\Support\MemberFieldRules::INITIAL_PATTERN }}"
                       title="Enter one letter, such as M." placeholder="M."
                       onblur="const initial = this.value.replace(/\.$/, '').toUpperCase(); if (/^\p{L}\p{M}*$/u.test(initial)) this.value = initial + '.'">
            @elseif ($field === 'publisher_name')
                @php($isIndividual = $item?->getAttribute('publisher_type') === 'Individual')
                <input class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" value="{{ $item?->getAttribute($field) }}"
                       data-publisher-name="{{ $prefix }}"
                       maxlength="255"
                       pattern="{{ $isIndividual ? \App\Http\Requests\SaveCatalogLookupRequest::INDIVIDUAL_PUBLISHER_PATTERN : \App\Http\Requests\SaveCatalogLookupRequest::PUBLISHER_PATTERN }}"
                       title="{{ $isIndividual ? 'Use letters, spaces, and initials only (e.g. Juan M. Dela Cruz).' : 'Use letters, numbers, spaces, and standard punctuation (. , & \' - /).' }}"
                       placeholder="{{ $isIndividual ? 'e.g. Juan M. Dela Cruz' : 'e.g. Rex Book Store, Inc.' }}"
                       onblur="this.value = this.value.trim().replace(/ +/g, ' ')" required>
            @else
                <input class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}" value="{{ $item?->getAttribute($field) }}">
            @endif
        </div>
    @endforeach
</div>
