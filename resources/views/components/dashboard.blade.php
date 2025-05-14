<div class="main-body">
    <div class="page-wrapper">
        <div class="page-body">
        @if(auth()->user()->sales)
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=1') }}">
                        <div class="card bg-c-yellow update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Bawah</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[1]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[1]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[1]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[1]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-1" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=2') }}">
                        <div class="card bg-c-green update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Zilfa</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[2]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[2]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[2]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[2]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-2" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=3') }}">
                        <div class="card bg-c-pink update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Tokopedia</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[3]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[3]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[3]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[3]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-3" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=5') }}">
                        <div class="card bg-c-lite-green update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Vira</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[5]->total ?? 0 }} </h5>
                                        @forelse ($stokJenisGudang5 as $item)
                                            <h6 class="text-white m-b-0">{{ $item->jenis }} : {{ $item->total ?? 0}} </h6>
                                        @empty
                                            <h6 class="text-white m-b-0">NA</h6>
                                        @endforelse
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-4" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-4 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=8') }}">
                        <div class="card bg-c-yellow update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Joko Cell (Semarang)</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[8]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[8]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[8]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[8]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-1" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-4 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=9') }}">
                        <div class="card bg-c-green update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Adit Cell (Jogja)</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[9]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[9]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[9]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[9]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-2" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-4 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=10') }}">
                        <div class="card bg-c-pink update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Popeye Cell (Purkem)</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[10]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[10]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[10]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[10]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-3" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-6 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=6') }}">
                        <div class="card bg-c-orenge update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Return</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[6]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[6]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[6]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[6]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-4" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-6 col-md-6">
                    <a href="{{ url('/stok-opname?gudang_id=7') }}">
                        <div class="card bg-c-orenge update-card">
                            <div class="card-block">
                                <div class="row align-items-end">
                                    <div class="col-8">
                                        <h4 class="text-white">Gudang Lain Lain</h4>
                                        <h5 class="text-white m-b-0">Total Stok : {{ $stokGudangs[7]->total ?? 0 }} </h5>
                                        <h6 class="text-white m-b-0">Box : {{ $stokBox[7]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">Batangan : {{ $stokBtg[7]->total ?? 0 }} </h6>
                                        <h6 class="text-white m-b-0">N/A : {{ $stokNa[7]->total ?? 0 }} </h6>
                                    </div>
                                    <div class="col-4 text-end">
                                        <canvas id="update-chart-4" height="50"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="text-white m-b-0"><i class="feather icon-clock text-white f-14 m-r-10"></i>update : {{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            @endif
            @if(auth()->user()->adm)
            <div class="row">
                <div class="col-xl-6 col-md-6">
                    <div class="card bg-c-lite-green update-card" style="position: relative; overflow: hidden;">
                        <div class="card-block">
                            <div class="row align-items-end">
                                <div class="col-8">
                                    <h4 class="text-white">Pengajuan Transfer Uang</h4>
                                </div>
                            </div>
                        </div>
                        <a href="{{ url('/todo-transfer') }}" style="
                            position: absolute; 
                            bottom: 20px; 
                            right: 20px; 
                            background: rgba(255, 255, 255, 0.2); 
                            border-radius: 10px; 
                            padding: 10px 15px; 
                            color: white; 
                            text-decoration: none; 
                            backdrop-filter: blur(10px); 
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            transition: background 0.3s, color 0.3s;
                        ">
                            Klik di sini
                        </a>
                    </div>
                </div>
                <div class="col-xl-6 col-md-6">
                    <div class="card bg-c-green update-card">
                        <div class="card-block">
                            <div class="row align-items-end">
                                <div class="col-8">
                                    <h4 class="text-white">Pengajuan Barang Tokopedia</h4>
                                </div>
                            </div>
                        </div>
                        <a href="{{ url('/req-tokped') }}" style="
                            position: absolute; 
                            bottom: 20px; 
                            right: 20px; 
                            background: rgba(255, 255, 255, 0.2); 
                            border-radius: 10px; 
                            padding: 10px 15px; 
                            color: white; 
                            text-decoration: none; 
                            backdrop-filter: blur(10px); 
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            transition: background 0.3s, color 0.3s;
                        ">
                            Klik di sini
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>