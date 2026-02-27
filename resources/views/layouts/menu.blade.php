<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li>
                    <a href="{{ route('home') }}" aria-expanded="false"><span class="hide-menu">Dashboard </span></a>
                </li>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false"><span class="hide-menu">Transaksi Satusehat
                        </span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ route('transaction.rawat-jalan.index') }}">Rawat Jalan</a></li>
                        <li><a href="{{ route('transaction.rawat-inap.index') }}">Rawat Inap</a></li>
                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false"><span class="hide-menu">Master
                            Data</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="{{ route('master_obat') }}">Master Obat</a></li>
                        <li><a href="{{ route('master_specimen.index') }}">Master Specimen</a></li>
                        <li><a href="{{ route('master_laboratory') }}">Master Tindakan Lab</a></li>
                        <li><a href="{{ route('master_radiology') }}">Master Tindakan Radiologi</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" aria-expanded="false"><span class="hide-menu">Log Transaksi</span></a>
                </li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
