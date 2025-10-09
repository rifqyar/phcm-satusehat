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
            <h4 class="card-title">Tambah Mapping Specimen</h4>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        <h4>Data Tindakan & Specimen</h4>
                    </div>

                    <form method="POST" action="{{ route('master_specimen.store') }}" class="mb-3">
                        @csrf

                        <div class="mb-3">
                            <label for="tindakan" class="form-label">Tindakan</label>
                            <select class="form-control" id="tindakan" name="tindakan">
                                @foreach ($tindakanData as $index => $lab)
                                    <option value="{{ $lab->ID_TINDAKAN }}">
                                        {{ $lab->NAMA_GRUP }} - {{ $lab->NAMA_TINDAKAN }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="specimen" class="form-label">Specimen</label>
                            <select class="form-control" id="specimen" name="specimen[]" multiple>
                                @foreach ($specimens as $specimen)
                                    <option value="{{ $specimen->code }}">
                                        {{ $specimen->display }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Specimen</button>
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

            $('#tindakan').select2({
                placeholder: 'Pilih Tindakan Lab',
                allowClear: true
            });
        })
    </script>
@endsection
