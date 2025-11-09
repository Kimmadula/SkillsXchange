<footer class="session-footer">
    <div class="footer-info">
        @php
            $startDate = \Carbon\Carbon::parse($trade->start_date);
            $duration = $startDate->diffForHumans(\Carbon\Carbon::now(), true);
            $sessionStatus = $trade->isExpired() ? 'Expired' : ($trade->status === 'ongoing' ? 'Active' : ucfirst($trade->status));
        @endphp
        Duration: {{ $duration }} • Status: {{ $sessionStatus }}
    </div>
    <div class="footer-actions">
        <button class="footer-btn btn-report" onclick="openReportUserModal()">⚠️ Report</button>
        <button class="footer-btn btn-end" onclick="endSession()">End Session</button>
    </div>
</footer>

