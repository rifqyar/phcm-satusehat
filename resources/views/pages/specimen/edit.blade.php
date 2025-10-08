@extends('layouts.app')

@push('before-style')
    <!-- Existing CSS links -->
    <!-- ... -->
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-js/dist/chartist-init.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('pages-css')
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Edit Mapping Specimen</h4>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        <h4>Data Tindakan & Specimen</h4>
                    </div>

                    <form method="POST" action="{{ route('master_specimen.update', $tindakan->KD_TIND) }}" class="mb-3">
                        <input type="hidden" name="_method" value="PATCH">
                        @csrf

                        <div class="mb-3">
                            <label for="tindakan" class="form-label">Tindakan</label>
                            <input type="text" class="form-control" id="tindakan" name="tindakan"
                                value="{{ $tindakan->NM_TIND }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="tindakan" class="form-label">Tindakan</label>
                            <input type="text" class="form-control" id="tindakan" name="tindakan"
                                value="{{ $tindakan->KD_TIND }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="specimen" class="form-label">Specimen</label>
                            <select class="form-control" id="specimen" name="specimen[]" multiple>
                                @foreach ($specimens as $specimen)
                                    <option value="{{ $specimen->code }}" @if ($mappings->contains('KODE_SPECIMEN', $specimen->code)) selected @endif>
                                        {{ $specimen->display }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('pages-js')
    <script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#specimen').select2({
                placeholder: 'Pilih Specimen / Sample Lab',
                allowClear: true
            });
        })
    </script>
@endsection
