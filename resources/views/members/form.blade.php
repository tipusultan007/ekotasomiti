@extends('layouts.app')

@section('title', $member->exists ? __('Edit Member') : __('Add Member'))

@section('content')
@php $isEdit = $member->exists; @endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">{{ $isEdit ? __('Edit Member') : __('Add New Member') }}</h4>
        <div class="text-muted small">
            @if ($isEdit)
                <span class="badge bg-primary-subtle text-primary">{{ $member->member_no }}</span>
                <span class="ms-1">{{ $member->name }}</span>
            @else
                {{ __('Fill in the member details below.') }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
        <button type="submit" form="memberForm" class="btn btn-primary rounded-pill px-3 fw-bold"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? __('Update Member') : __('Create Member') }}</button>
    </div>
</div>

<form method="POST" action="{{ $isEdit ? route('members.update', $member) : route('members.store') }}" enctype="multipart/form-data" id="memberForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Basic Information --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-person-vcard text-primary"></i>
                    <span>{{ __('Basic Information') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Membership Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="membership_date" class="form-control" value="{{ old('membership_date', $member->membership_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Member Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $member->name) }}" placeholder="{{ __('Full name in English') }}">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Name (Bangla)') }}</label>
                            <input type="text" name="name_bn" class="form-control" value="{{ old('name_bn', $member->name_bn) }}" placeholder="{{ __('সদস্যের নাম') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Father / Husband Name') }}</label>
                            <input type="text" name="father_husband_name" class="form-control" value="{{ old('father_husband_name', $member->father_husband_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Mother Name') }}</label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $member->mother_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Date of Birth') }}</label>
                            <input type="date" name="dob" class="form-control" value="{{ old('dob', $member->dob?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Gender') }}</label>
                            <select name="gender" class="form-select">
                                @foreach (['male', 'female', 'other'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender', $member->gender) === $gender)>{{ __(ucfirst($gender)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Occupation') }}</label>
                            <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $member->occupation) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact & Identity --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-telephone text-success"></i>
                    <span>{{ __('Contact & Identity') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Mobile') }}</label>
                            <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $member->mobile) }}" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('NID') }}</label>
                            <input type="text" name="nid" class="form-control" value="{{ old('nid', $member->nid) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $member->address) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Nominees --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people text-warning"></i>
                        <span>{{ __('Nominees') }}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addNominee"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Nominee') }}</button>
                </div>
                <div class="card-body">
                    <div id="nominees">
                        @php $oldNominees = old('nominees', $member->exists ? $member->nominees->toArray() : []); @endphp
                        @foreach ($oldNominees as $index => $nominee)
                            <div class="row g-2 nominee-row mb-2 align-items-center">
                                <div class="col-md-3">
                                    <input type="text" name="nominees[{{ $index }}][name]" class="form-control" placeholder="{{ __('Nominee name') }}" value="{{ $nominee['name'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="nominees[{{ $index }}][relationship]" class="form-control" placeholder="{{ __('Relation') }}" value="{{ $nominee['relationship'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="nominees[{{ $index }}][nid]" class="form-control" placeholder="{{ __('NID') }}" value="{{ $nominee['nid'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="nominees[{{ $index }}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}" value="{{ $nominee['mobile'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" name="nominees[{{ $index }}][percentage]" class="form-control" placeholder="%" value="{{ $nominee['percentage'] ?? '' }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-nominee" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="nomineeEmpty" class="text-muted small {{ count($oldNominees) ? 'd-none' : '' }}"><i class="bi bi-info-circle me-1"></i>{{ __('No nominees added yet.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="position-sticky" style="top: 84px;">
                {{-- Assignment --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-info"></i>
                        <span>{{ __('Area & Assignment') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Area') }} <span class="text-danger">*</span></label>
                            <select name="area_id" id="areaSelect" class="form-select select2" required>
                                <option value="">{{ __('Select Area') }}</option>
                                @foreach ($areas as $area)
                                    @php
                                        $officers = $area->fieldOfficers;
                                        $officerNames = $officers->isNotEmpty() ? $officers->pluck('name')->join(', ') : __('No officer assigned');
                                    @endphp
                                    <option value="{{ $area->id }}"
                                        data-officer="{{ $officerNames }}"
                                        @selected(old('area_id', $member->area_id) == $area->id)>
                                        {{ $area->name }} ({{ $area->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="assignedOfficerBox">
                            <label class="form-label text-muted small mb-1">{{ __('Assigned Field Officer') }}</label>
                            <div class="p-2 bg-light rounded-2 border d-flex align-items-center gap-2 small">
                                <i class="bi bi-person-badge text-primary"></i>
                                <span id="assignedOfficerText" class="fw-medium text-dark">
                                    {{ $member->fieldOfficer?->name ?? ($member->area?->fieldOfficers?->first()?->name ?? __('Auto-assigned based on selected Area')) }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                @foreach (['active', 'inactive', 'suspended', 'closed'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $member->status) === $status)>{{ __(ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Photo & Notes --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-image text-danger"></i>
                        <span>{{ __('Photo & Notes') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div id="photoPreview">
                                @if ($member->photo_path)
                                    <img src="{{ asset('storage/' . $member->photo_path) }}" class="rounded-3 border" style="width:110px;height:110px;object-fit:cover;">
                                @else
                                    <div class="border rounded-3 d-inline-flex align-items-center justify-content-center text-muted" style="width:110px;height:110px;background:#f8fafc;">
                                        <i class="bi bi-person fs-1"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Photo') }}</label>
                            <input type="file" name="photo" id="photoInput" class="form-control" accept="image/*">
                            <div class="form-text">{{ __('JPG or PNG, max 2MB') }}</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $member->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? __('Update Member') : __('Create Member') }}</button>
                        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let nomineeIndex = {{ count($oldNominees) }};

    function toggleNomineeEmpty() {
        document.getElementById('nomineeEmpty')?.classList.toggle('d-none', document.querySelectorAll('.nominee-row').length > 0);
    }

    document.getElementById('addNominee')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 nominee-row mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-3"><input type="text" name="nominees[${nomineeIndex}][name]" class="form-control" placeholder="{{ __('Nominee name') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][relationship]" class="form-control" placeholder="{{ __('Relation') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][nid]" class="form-control" placeholder="{{ __('NID') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}"></div>
            <div class="col-md-2"><div class="input-group"><input type="number" step="0.01" min="0" max="100" name="nominees[${nomineeIndex}][percentage]" class="form-control" placeholder="%"><span class="input-group-text">%</span></div></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-nominee" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button></div>`;
        document.getElementById('nominees').appendChild(row);
        nomineeIndex++;
        row.querySelector('.remove-nominee').addEventListener('click', () => { row.remove(); toggleNomineeEmpty(); });
        toggleNomineeEmpty();
    });

    document.querySelectorAll('.remove-nominee').forEach(btn => btn.addEventListener('click', () => { btn.closest('.nominee-row').remove(); toggleNomineeEmpty(); }));

    // Photo preview
    document.getElementById('photoInput')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('photoPreview').innerHTML =
                '<img src="' + e.target.result + '" class="rounded-3 border" style="width:110px;height:110px;object-fit:cover;">';
        };
        reader.readAsDataURL(file);
    });

    // Area change -> update assigned officer display
    const areaSelect = document.getElementById('areaSelect');
    if (areaSelect) {
        const updateOfficerDisplay = () => {
            const selectedOption = areaSelect.options[areaSelect.selectedIndex];
            const officer = selectedOption?.getAttribute('data-officer') || "{{ __('Auto-assigned based on selected Area') }}";
            const officerText = document.getElementById('assignedOfficerText');
            if (officerText) {
                officerText.textContent = officer;
            }
        };
        areaSelect.addEventListener('change', updateOfficerDisplay);
        if (window.jQuery) {
            $(areaSelect).on('select2:select', updateOfficerDisplay);
        }
    }
</script>
@endpush