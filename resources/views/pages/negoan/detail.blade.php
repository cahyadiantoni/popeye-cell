@extends('layouts.main')

@section('title', 'Detail Negoan')
@section('content')
<style>
    /* Chat Message Styles */
    .chat-message {
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 5px;
        max-width: 70%; /* Limit width of messages */
        position: relative; /* For positioning the border */
    }

    /* Styles for messages from the logged-in user */
    .chat-message[style*="right"] {
        background-color: #007bff; /* Blue background for user's messages */
        color: white; /* White text for user's messages */
        margin-left: auto; /* Align to the right */
        border: 1px solid #0056b3; /* Darker blue border for user's messages */
    }

    /* Styles for messages from others */
    .chat-message[style*="left"] {
        background-color: #e9ecef; /* Light gray background for others' messages */
        color: black; /* Black text for others' messages */
        border: 1px solid #ced4da; /* Gray border for others' messages */
    }

    /* Optional: Add a shadow effect to messages */
    .chat-message {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    }

    /* Timestamp Styles */
    .timestamp [style*="right"]{
        color: white; 
        font-size: 0.8em; /* Slightly larger font size */
        margin-top: 5px; /* Space above the timestamp */
        display: block; /* Ensure it appears on a new line */
    }

    .timestamp [style*="left"]{
        color: black; 
        font-size: 0.8em; /* Slightly larger font size */
        margin-top: 5px; /* Space above the timestamp */
        display: block; /* Ensure it appears on a new line */
    }
</style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="main-body">
        <div class="page-wrapper">
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Detail Negoan</h4>
                                <span>Nomor Negoan: {{ $negoan->nomor_return }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="{{ route('negoan.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <!-- Pesan Success atau Error -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Informasi Negoan -->
                <div class="card">
                    <div class="card-header">
                        <h5>Informasi Negoan</h5>
                    </div>
                    <div class="card-block">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th width="30%">Tipe</th>
                                    <td>{{ $negoan->tipe }}</td>
                                </tr>
                                <tr>
                                    <th>Grade</th>
                                    <td>{{ $negoan->grade }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Negoan</th>
                                    <td>{{ \Carbon\Carbon::parse($negoan->created_at)->translatedFormat('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Harga Awal</th>
                                    <td>Rp. {{ number_format($negoan->harga_awal, 0, ',', '.') }}
                                        @if ($negoan->is_manual == 1)
                                            (Manual Input)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Harga Negoan</th>
                                    <td>Rp. {{ number_format($negoan->harga_nego, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Catatan Nego</th>
                                    <td>{{ $negoan->note_nego }}</td>
                                </tr>
                                <tr>
                                    <th>Petugas</th>
                                    <td>{{ $negoan->user->name }}</td>
                                </tr>
                                @if ($negoan->status == 1)
                                    <tr>
                                        <th>Harga Disetujui</th>
                                        <td>Rp. {{ number_format($negoan->harga_acc, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Catatan Disetujui</th>
                                        <td>{{ $negoan->note_acc }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if ($negoan->status == 0)
                                            <span class="badge bg-warning">Proses</span>
                                        @elseif ($negoan->status == 1)
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif ($negoan->status == 2)
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>History Negoan</h5>
                    </div>
                    <div class="card-block table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>User</th>
                                    <th>Tanggal</th>
                                    <th>Harga Awal</th>
                                    <th>Harga Nego</th>
                                    <th>Catatan Nego</th>
                                    <th>Harga ACC</th>
                                    <th>Catatan ACC</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historyNego as $index => $history)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $history->user->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($history->updated_at)->translatedFormat('d F Y') }}</td>
                                    <td>{{ $history->harga_awal }}</td>
                                    <td>{{ $history->harga_nego }}</td>
                                    <td>{{ $history->note_nego }}</td>
                                    <td>{{ $history->harga_acc }}</td>
                                    <td>{{ $history->note_acc }}</td>
                                    <td>
                                        @if ($history->status == 0)
                                            <span class="badge bg-warning">Proses</span>
                                        @elseif ($history->status == 1)
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif ($history->status == 2)
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($roleUser=="admin")
                <div class="card">
                    <div class="card-header">
                        <h5>Persetujuan Harga Negoan</h5>
                    </div>
                    <div class="card-block">
                    <form method="POST" action="{{ route('negoan.update', $negoan->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="mb-3 row">
                                <label class="form-label col-sm-2 col-form-label">Harga Acc</label>
                                <div class="col-sm-10">
                                    <input type="number" name="harga_acc" class="form-control" placeholder="Ketik Harga yang Disetujui" required id="harga_acc">
                                    <small class="form-text text-muted" id="harga_acc_display">{{ 'Rp. 0' }}</small>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="form-label col-sm-2 col-form-label">Note Acc</label>
                                <div class="col-sm-10">
                                    <textarea name="note_acc" class="form-control" placeholder="Tambahkan catatan jika diperlukan" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="form-label col-sm-2 col-form-label">Negoan Disetujui?</label>
                                <div class="col-sm-10">
                                        <input type="radio" name="status" value="1" checked onclick="toggleManualInput()"> Disetujui
                                        <input type="radio" name="status" value="2" onclick="toggleManualInput()"> Ditolak
                                </div>
                            </div>
                            <!-- Tambahkan tombol submit di sini -->
                            <div class="row">
                                <div class="col-sm-12 text-end"> <!-- Aligns the button to the right -->
                                    <button type="submit" class="btn btn-primary btn-round">Submit Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Chat Negoan -->
                <div class="card">
                    <div class="card-header">
                        <h5>Chat Negoan</h5>
                    </div>
                    <div class="card-block">
                        <div class="">
                            @if($chats->count() === 0)
                                <div class="chat-message text-center">
                                    <em>Belum ada chat.</em>
                                </div>
                            @else
                                @foreach($chats as $chat)
                                    <div class="chat-message" style="text-align: {{ $chat->user->id === auth()->id() ? 'right' : 'left' }};">
                                        <strong>{{ $chat->user->name }} ({{ \Carbon\Carbon::parse($chat->created_at)->format('d F Y H:i') }}) :</strong> 
                                        <br> {{ $chat->isi }} <br>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <form method="POST" action="{{ route('negoan.chat.store') }}">
                            @csrf
                            <input type="hidden" name="t_negoan_id" value="{{ $negoan->id }}">
                            <div class="input-group mt-3">
                                <input type="text" name="isi" class="form-control" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Function to format number as currency
            function formatCurrency(value) {
                return 'Rp. ' + new Intl.NumberFormat('id-ID').format(value);
            }

            // Update display for harga_acc
            $('#harga_acc').on('input', function() {
                const value = $(this).val();
                $('#harga_acc_display').text(formatCurrency(value));
            });
        });
    </script>

@endsection
