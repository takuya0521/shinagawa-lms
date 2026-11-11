@if ($errors->any())
    <x-feedback type="error" :message="implode(' ', $errors->all())" class="mb-4" />
@endif
