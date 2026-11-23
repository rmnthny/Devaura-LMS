<fieldset>
    <legend class="h5 mb-3">Membership</legend>
    <div class="row g-3">
    <div class="col-sm-6">
        <label class="form-label" for="member_role">Member type</label>
        <select class="form-select" name="member_role" id="member_role" required>
            @foreach (['Student', 'Faculty'] as $role)
                <option @selected(old('member_role', $member?->member_role) === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6">
        <label class="form-label" id="reference-label" for="reference_number">LRN</label>
        <input class="form-control" id="reference_number" name="reference_number" inputmode="numeric" pattern="[0-9]{7}|[0-9]{12}" maxlength="12" aria-describedby="reference-help" value="{{ old('reference_number', $member?->reference_number) }}" required>
        <div id="reference-help" class="form-text">Enter exactly 12 digits.</div>
    </div>
    <div class="col-12" id="faculty-department">
        <label class="form-label" for="department_id">Subject department</label>
        <select class="form-select" id="department_id" name="department_id">
            <option value="">Select a department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->getKey() }}" @selected(old('department_id', $member?->department_id) == $department->getKey())>{{ $department->department_name }}</option>
            @endforeach
        </select>
    </div>
    </div>
</fieldset>
<fieldset class="border-top mt-4 pt-3">
    <legend class="float-none w-auto h5 pe-2 mb-2">Personal details</legend>
    <div class="row g-3">
    @include('members.names', ['profile' => $member])
    <div class="col-12">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" name="email" id="email" maxlength="254" autocomplete="email"
               pattern="{{ \App\Support\MemberFieldRules::EMAIL_PATTERN }}" placeholder="name@example.com" value="{{ old('email', $member?->user?->email) }}" required>
    </div>
    </div>
</fieldset>
<script>
    (() => {
        const role = document.getElementById('member_role');
        const reference = document.getElementById('reference_number');
        const department = document.getElementById('department_id');
        const sync = () => {
            const faculty = role.value === 'Faculty';
            const digits = faculty ? 7 : 12;
            reference.disabled = !role.value;
            document.getElementById('reference-label').textContent = faculty ? 'DepEd Employee Number' : (role.value ? 'LRN' : 'Reference number');
            document.getElementById('reference-help').textContent = role.value ? 'Enter exactly ' + digits + ' digits.' : '';
            reference.pattern = '[0-9]{' + digits + '}';
            reference.maxLength = digits;
            document.getElementById('faculty-department').hidden = !faculty;
            department.disabled = !faculty;
            department.required = faculty;
        };
        role.addEventListener('change', () => { reference.value = ''; sync(); });
        reference.addEventListener('beforeinput', event => {
            if (event.data && /[^0-9]/.test(event.data)) event.preventDefault();
        });
        reference.addEventListener('input', () => {
            const digits = reference.value.replace(/[^0-9]/g, '').slice(0, reference.maxLength);
            if (digits !== reference.value) reference.value = digits;
        });
        sync();
    })();
</script>
