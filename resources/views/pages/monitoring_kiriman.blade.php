@extends('layouts.app')

@push('before-style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .queue-table-wrapper {
            overflow-x: auto;
        }
        .badge-pending {
            background-color: #ffb22b;
            color: #fff;
        }
        .badge-failed {
            background-color: #fc4b6c;
            color: #fff;
        }
        .badge-empty {
            background-color: #e0e0e0;
            color: #666;
        }
        .queue-card-header {
            cursor: pointer;
            user-select: none;
        }
        .queue-card-header:hover {
            background-color: #f9f9f9;
        }
        .param-cell {
            font-size: 0.8rem;
            font-family: monospace;
            white-space: nowrap;
        }
        .exception-cell {
            font-size: 0.78rem;
            color: #fc4b6c;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .job-id-cell {
            font-size: 0.75rem;
            font-family: monospace;
            color: #999;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .job-class-cell {
            font-size: 0.8rem;
            font-family: monospace;
            color: #1e88e5;
        }
        .chevron-icon {
            transition: transform 0.2s ease;
        }
        .chevron-icon.rotated {
            transform: rotate(180deg);
        }
        .summary-icon {
            font-size: 2rem;
            opacity: 0.85;
        }
        /* Queue tabs */
        .queue-tabs {
            display: flex;
            border-bottom: 2px solid #f0f0f0;
            padding: 0 1rem;
        }
        .queue-tab {
            padding: 0.6rem 1.2rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.15s, border-color 0.15s;
            user-select: none;
        }
        .queue-tab:hover {
            color: #555;
        }
        .queue-tab.active {
            color: #ffb22b;
            border-bottom-color: #ffb22b;
        }
        .queue-tab.tab-failed.active {
            color: #fc4b6c;
            border-bottom-color: #fc4b6c;
        }
        .queue-tab-pane {
            display: none;
        }
        .queue-tab-pane.active {
            display: block;
        }
    </style>
@endpush

@section('content')

    {{-- Page Title --}}
    <div class="row page-titles">
        <div class="col-md-5 col-8 align-self-center">
            <h3 class="text-themecolor">Monitoring Kiriman</h3>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Monitoring Kiriman</li>
            </ol>
        </div>
        <div class="col-md-7 col-4 align-self-center">
            <div class="d-flex m-t-10 justify-content-end align-items-center" style="gap: 0.5rem;">
                @if($redisConnected)
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-circle fa-xs"></i> Redis Connected
                    </span>
                @else
                    <span class="badge badge-danger px-3 py-2">
                        <i class="fas fa-circle fa-xs"></i> Redis Disconnected
                    </span>
                @endif
                <button class="btn btn-sm btn-light border" id="refresh-btn" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span id="ws-status" class="badge badge-secondary px-3 py-2">
                    <i class="fas fa-circle fa-xs"></i> Connecting...
                </span>
                <span class="text-muted text-right" style="font-size:0.78rem;" id="last-loaded">
                    Last loaded: {{ now()->format('Y-m-d H:i:s') }}
                </span>
            </div>
        </div>
    </div>

    @php
        $totalPending = collect($monitoringData)->sum(function($q) { return count($q['pending']); });
        $totalFailed  = collect($monitoringData)->sum(function($q) { return count($q['failed']); });
        $totalQueues  = count($monitoringData);
    @endphp

    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="m-r-20">
                            <span class="summary-icon text-info"><i class="fas fa-layer-group"></i></span>
                        </div>
                        <div>
                            <h2 class="font-bold m-b-0">{{ $totalQueues }}</h2>
                            <small class="text-muted">Total Queues</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="m-r-20">
                            <span class="summary-icon text-warning"><i class="fas fa-hourglass-half"></i></span>
                        </div>
                        <div>
                            <h2 class="font-bold m-b-0" id="summary-pending">{{ $totalPending }}</h2>
                            <small class="text-muted">Pending Jobs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="m-r-20">
                            <span class="summary-icon text-danger"><i class="fas fa-times-circle"></i></span>
                        </div>
                        <div>
                            <h2 class="font-bold m-b-0" id="summary-failed">{{ $totalFailed }}</h2>
                            <small class="text-muted">Failed Jobs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Queue Cards --}}
    @foreach($monitoringData as $index => $queue)
    @php
        $pendingCount = count($queue['pending']);
        $failedCount  = count($queue['failed']);
        $hasJobs      = $pendingCount > 0 || $failedCount > 0;
        $defaultTab   = $failedCount > 0 ? 'failed' : 'pending';
    @endphp

    <div class="card">
        {{-- Card Header --}}
        <div class="card-header queue-card-header d-flex align-items-center justify-content-between"
            data-queue="{{ $queue['queue'] }}"
            onclick="toggleQueue('queue-body-{{ $index }}', 'chevron-{{ $index }}')">
            <div class="d-flex align-items-center">
                <i class="fas fa-circle fa-xs m-r-10 {{ $failedCount > 0 ? 'text-danger' : ($pendingCount > 0 ? 'text-warning' : 'text-success') }}"></i>
                <h4 class="card-title m-b-0">{{ $queue['queue'] }}</h4>
            </div>
            <div class="d-flex align-items-center" style="gap: 0.4rem;">
                <span class="badge badge-pending px-2 py-1"
                    id="badge-pending-{{ $queue['queue'] }}"
                    style="{{ $pendingCount > 0 ? '' : 'display:none' }}">
                    {{ $pendingCount }} pending
                </span>
                <span class="badge badge-failed px-2 py-1"
                    id="badge-failed-{{ $queue['queue'] }}"
                    style="{{ $failedCount > 0 ? '' : 'display:none' }}">
                    {{ $failedCount }} failed
                </span>
                <span class="badge badge-empty px-2 py-1"
                    id="badge-empty-{{ $queue['queue'] }}"
                    style="{{ !$hasJobs ? '' : 'display:none' }}">
                    empty
                </span>
                <i class="fas fa-chevron-down chevron-icon {{ $hasJobs ? 'rotated' : '' }}" id="chevron-{{ $index }}"></i>
            </div>
        </div>

        {{-- Card Body --}}
        <div class="card-body p-0" id="queue-body-{{ $index }}" style="{{ $hasJobs ? '' : 'display:none' }}">

            {{-- Tabs --}}
            <div class="queue-tabs">
                <div class="queue-tab {{ $defaultTab === 'pending' ? 'active' : '' }}"
                     id="tab-pending-{{ $queue['queue'] }}"
                     onclick="switchTab(event, '{{ $queue['queue'] }}', 'pending')">
                    <i class="fas fa-hourglass-half"></i>
                    Pending (<span id="pending-count-{{ $queue['queue'] }}">{{ $pendingCount }}</span>)
                </div>
                <div class="queue-tab tab-failed {{ $defaultTab === 'failed' ? 'active' : '' }}"
                     id="tab-failed-{{ $queue['queue'] }}"
                     onclick="switchTab(event, '{{ $queue['queue'] }}', 'failed')">
                    <i class="fas fa-times-circle"></i>
                    Failed (<span id="failed-count-{{ $queue['queue'] }}">{{ $failedCount }}</span>)
                </div>
            </div>

            {{-- Pending Pane --}}
            <div class="queue-tab-pane {{ $defaultTab === 'pending' ? 'active' : '' }}"
                 id="pane-pending-{{ $queue['queue'] }}">
                <div class="queue-table-wrapper">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light" id="pending-thead-{{ $queue['queue'] }}">
                            <tr>
                                <th>UUID</th>
                                <th>Job Class</th>
                                <th>Attempts</th>
                                <th>No</th>
                                @php
                                    $paramKeys = collect($queue['pending'])
                                        ->flatMap(function($job) { return array_keys($job['params'] ?? []); })
                                        ->unique()
                                        ->values();
                                @endphp
                                @foreach($paramKeys as $key)
                                    <th>{{ $key }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="pending-tbody-{{ $queue['queue'] }}">
                            @forelse($queue['pending'] as $job)
                            @php $p = $job['params'] ?? []; @endphp
                            <tr>
                                <td><div class="job-id-cell" title="{{ $job['id'] ?? '' }}">{{ $job['id'] ?? '—' }}</div></td>
                                <td><div class="job-class-cell">{{ class_basename($job['job_class'] ?? '—') }}</div></td>
                                <td><span class="badge badge-info">{{ $job['attempts'] ?? 0 }}</span></td>
                                <td class="param-cell text-muted">{{ $loop->iteration }}</td>
                                @foreach($paramKeys as $key)
                                    <td class="param-cell">{{ $p[$key] ?? '—' }}</td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ 4 + count($paramKeys) }}" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox m-r-5"></i> No pending jobs
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Failed Pane --}}
            <div class="queue-tab-pane {{ $defaultTab === 'failed' ? 'active' : '' }}"
                 id="pane-failed-{{ $queue['queue'] }}">
                <div class="queue-table-wrapper pb-3">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Job Class</th>
                                <th>Attempts</th>
                                <th>Failed At</th>
                                <th>Error Message</th>
                                <th>Connection</th>
                            </tr>
                        </thead>
                        <tbody id="failed-tbody-{{ $queue['queue'] }}">
                            @forelse($queue['failed'] as $job)
                            <tr>
                                <td class="param-cell text-muted">{{ $job['id'] }}</td>
                                <td><div class="job-class-cell">{{ class_basename($job['job_class'] ?? '—') }}</div></td>
                                <td><span class="badge badge-warning">{{ $job['attempts'] ?? '—' }}</span></td>
                                <td class="param-cell text-muted" style="white-space:nowrap">{{ $job['failed_at'] }}</td>
                                <td>
                                    <div class="exception-cell" title="{{ $job['exception']['message'] ?? '' }}">
                                        {{ $job['exception']['message'] ?? '—' }}
                                    </div>
                                </td>
                                <td class="param-cell text-muted">{{ $job['connection'] ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle text-success m-r-5"></i> No failed jobs
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endforeach

@endsection

@push('after-script')
<script>
(function () {
    const ws = new WebSocket('ws://localhost:6001');
    const statusEl = document.getElementById('ws-status');

    ws.onopen = () => {
        statusEl.className = 'badge badge-success px-3 py-2';
        statusEl.innerHTML = '<i class="fas fa-circle fa-xs"></i> Live';
    };

    ws.onclose = () => {
        statusEl.className = 'badge badge-danger px-3 py-2';
        statusEl.innerHTML = '<i class="fas fa-circle fa-xs"></i> Disconnected';
        setTimeout(() => location.reload(), 5000);
    };

    ws.onerror = (err) => console.error('WebSocket error:', err);

    ws.onmessage = async (event) => {
        const msg = JSON.parse(event.data);
        if (msg.type !== 'queue_update') return;
        await refreshJobTables();
    };

    async function refreshJobTables() {
        try {
            const res  = await fetch('/monitoring-kiriman/jobs');
            const json = await res.json();
            if (json.status !== 'success') return;

            let totalPending = 0;
            let totalFailed  = 0;

            json.data.forEach(({ queue, pending, pending_total, failed, failed_total }) => {
                const pendingCount = pending_total;
                const failedCount  = failed_total;

                totalPending += pendingCount;
                totalFailed  += failedCount;

                // --- Header badges ---
                const badgePending = document.getElementById(`badge-pending-${queue}`);
                const badgeFailed  = document.getElementById(`badge-failed-${queue}`);
                const badgeEmpty   = document.getElementById(`badge-empty-${queue}`);

                if (badgePending) {
                    badgePending.textContent = `${pendingCount} pending`;
                    badgePending.style.display = pendingCount > 0 ? '' : 'none';
                }
                if (badgeFailed) {
                    badgeFailed.textContent = `${failedCount} failed`;
                    badgeFailed.style.display = failedCount > 0 ? '' : 'none';
                }
                if (badgeEmpty) {
                    badgeEmpty.style.display = (pendingCount === 0 && failedCount === 0) ? '' : 'none';
                }

                // --- Tab counts ---
                const pendingCountEl = document.getElementById(`pending-count-${queue}`);
                const failedCountEl  = document.getElementById(`failed-count-${queue}`);
                if (pendingCountEl) pendingCountEl.textContent = pendingCount;
                if (failedCountEl)  failedCountEl.textContent  = failedCount;

                // --- Dot color ---
                const header = document.querySelector(`[data-queue="${queue}"]`);
                if (header) {
                    const dot = header.querySelector('.fa-circle');
                    if (dot) {
                        dot.className = `fas fa-circle fa-xs m-r-10 ${
                            failedCount > 0 ? 'text-danger' : (pendingCount > 0 ? 'text-warning' : 'text-success')
                        }`;
                    }
                }

                // --- Rebuild tables ---
                rebuildPendingTable(queue, pending, pending_total);
                rebuildFailedTable(queue, failed, failed_total);
            });

            // --- Summary ---
            const summaryPending = document.getElementById('summary-pending');
            const summaryFailed  = document.getElementById('summary-failed');
            if (summaryPending) summaryPending.textContent = totalPending;
            if (summaryFailed)  summaryFailed.textContent  = totalFailed;

            // --- Timestamp ---
            const lastLoaded = document.getElementById('last-loaded');
            if (lastLoaded) lastLoaded.textContent = 'Last updated: ' + new Date().toLocaleTimeString();

        } catch (err) {
            console.error('Failed to fetch job details:', err);
        }
    }

    function rebuildPendingTable(queue, jobs, total) {
        const tbody = document.getElementById(`pending-tbody-${queue}`);
        const thead = document.getElementById(`pending-thead-${queue}`);
        if (!tbody) return;

        if (!jobs || jobs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="fas fa-inbox m-r-5"></i> No pending jobs
                    </td>
                </tr>`;
            return;
        }

        const paramKeys = [...new Set(jobs.flatMap(j => Object.keys(j.params || {})))];

        if (thead) {
            thead.innerHTML = `<tr>
                <th>UUID</th><th>Job Class</th><th>Attempts</th><th>No</th>
                ${paramKeys.map(k => `<th>${k}</th>`).join('')}
            </tr>`;
        }

        tbody.innerHTML = jobs.map((job, i) => `
            <tr>
                <td><div class="job-id-cell" title="${job.id || ''}">${job.id || '—'}</div></td>
                <td><div class="job-class-cell">${(job.job_class || '—').split('\\\\').pop()}</div></td>
                <td><span class="badge badge-info">${job.attempts || 0}</span></td>
                <td class="param-cell text-muted">${i + 1}</td>
                ${paramKeys.map(k => `<td class="param-cell">${job.params?.[k] ?? '—'}</td>`).join('')}
            </tr>
        `).join('');

        if (total > jobs.length) {
            const note = document.createElement('tr');
            note.innerHTML = `
                <td colspan="99" class="text-center text-muted py-2" style="font-size:0.78rem">
                    <i class="fas fa-info-circle"></i> Showing ${jobs.length} of ${total} pending jobs
                </td>`;
            tbody.appendChild(note);
        }
    }

    function rebuildFailedTable(queue, jobs, total) {
        const tbody = document.getElementById(`failed-tbody-${queue}`);
        if (!tbody) return;

        if (!jobs || jobs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <i class="fas fa-check-circle text-success m-r-5"></i> No failed jobs
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = jobs.map(job => `
            <tr>
                <td class="param-cell text-muted">${job.id}</td>
                <td><div class="job-class-cell">${(job.job_class || '—').split('\\\\').pop()}</div></td>
                <td><span class="badge badge-warning">${job.attempts ?? '—'}</span></td>
                <td class="param-cell text-muted" style="white-space:nowrap">${job.failed_at}</td>
                <td><div class="exception-cell" title="${job.exception?.message || ''}">${job.exception?.message || '—'}</div></td>
                <td class="param-cell text-muted">${job.connection || '—'}</td>
            </tr>
        `).join('');

        if (total > jobs.length) {
            tbody.innerHTML += `
                <tr>
                    <td colspan="6" class="text-center text-muted py-2" style="font-size:0.78rem">
                        <i class="fas fa-info-circle"></i> Showing ${jobs.length} of ${total} failed jobs
                    </td>
                </tr>`;
        }
    }

    // Initial load
    refreshJobTables();
})();

function toggleQueue(bodyId, chevronId) {
    const body    = document.getElementById(bodyId);
    const chevron = document.getElementById(chevronId);
    const isOpen  = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    chevron.classList.toggle('rotated', !isOpen);
}

function switchTab(event, queue, tab) {
    // Stop click from bubbling up to toggleQueue on the card header
    event.stopPropagation();

    ['pending', 'failed'].forEach(t => {
        document.getElementById(`tab-${t}-${queue}`)?.classList.remove('active');
        document.getElementById(`pane-${t}-${queue}`)?.classList.remove('active');
    });

    document.getElementById(`tab-${tab}-${queue}`)?.classList.add('active');
    document.getElementById(`pane-${tab}-${queue}`)?.classList.add('active');
}
</script>
@endpush