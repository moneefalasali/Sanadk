@php
    $unreadNotifications = $unreadNotifications ?? 0;
    $nameParts = preg_split('/\s+/', trim(Auth::user()->name));
    $userInitials = '';
    foreach ($nameParts as $part) {
        $userInitials .= mb_substr($part, 0, 1);
    }
    $userInitials = mb_strtoupper(mb_substr($userInitials, 0, 2));
@endphp

<div class="header-profile">
    <div class="d-flex align-items-center gap-3">
        <div class="position-relative">
            <div class="rounded-circle border border-2 border-white shadow d-flex align-items-center justify-content-center bg-primary text-white" style="width: 48px; height: 48px; font-weight: 700;">
                {{ $userInitials }}
            </div>
            <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" style="width: 12px; height: 12px;"></span>
        </div>
        <div>
            <h2 class="h5 mb-0 fw-bold text-white">{{ Auth::user()->name }}</h2>
            <p class="small mb-0 opacity-75">{{ $title ?? 'لوحة التحكم' }}</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('map') }}" class="text-white">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
        </a>
        <a href="{{ route('ai-chat') }}" class="text-white">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L1 23l6.71-1.97c1.31.61 2.75.97 4.29.97 5.52 0 10-4.48 10-10S17.52 2 12 2zm0 2c4.41 0 8 3.59 8 8s-3.59 8-8 8c-1.18 0-2.3-.26-3.33-.72L5 19.23l1.67-1.23c-.49-.92-.83-1.96-.83-3.07 0-4.41 3.59-8 8-8z"/>
                <circle cx="8.5" cy="11.5" r="1.5"/>
                <circle cx="12" cy="11.5" r="1.5"/>
                <circle cx="15.5" cy="11.5" r="1.5"/>
            </svg>
        </a>
        <a href="{{ route('notifications') }}" class="text-white position-relative">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
            </svg>
            @if($unreadNotifications > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">{{ $unreadNotifications }}</span>
            @endif
        </a>
    </div>
</div>