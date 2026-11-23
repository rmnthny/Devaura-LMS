@foreach (['last_name' => 'Last name', 'first_name' => 'First name', 'middle_initial' => 'Middle initial'] as $field => $label)
    <div class="col-sm-4">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        @if ($field === 'middle_initial')
            <input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $profile?->getAttribute($field)) }}"
                   maxlength="5" pattern="{{ \App\Support\MemberFieldRules::INITIAL_PATTERN }}" placeholder="M."
                   title="Enter one letter, such as M." @if ($showNameExample ?? false) aria-describedby="name-example" @endif
                   onblur="const initial = this.value.replace(/\.$/, '').toUpperCase(); if (/^\p{L}\p{M}*$/u.test(initial)) this.value = initial + '.'">
        @else
            <input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $profile?->getAttribute($field)) }}"
                   maxlength="50" pattern="{{ \App\Support\MemberFieldRules::NAME_PATTERN }}" title="Use letters and spaces only."
                   autocomplete="{{ $field === 'last_name' ? 'family-name' : 'given-name' }}"
                   @if ($showNameExample ?? false) placeholder="{{ $field === 'last_name' ? 'Dela Cruz' : 'Juan' }}" aria-describedby="name-example" @endif
                   oninput="this.value = this.value.replace(/[^\p{L}\p{M} ]/gu, '')"
                   onblur="this.value = this.value.trim().replace(/ +/g, ' ')" required>
        @endif
    </div>
@endforeach
