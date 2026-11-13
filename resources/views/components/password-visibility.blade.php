@props(['inputId', 'label' => 'パスワード'])

<div class="relative mt-2">
    {{ $slot }}
    <button
        type="button"
        class="group absolute inset-y-0 right-0 grid w-11 place-items-center rounded-lg lms-link-primary"
        data-password-toggle
        aria-controls="{{ $inputId }}"
        aria-pressed="false"
        aria-label="{{ $label }}を表示する"
        data-password-label="{{ $label }}"
    >
        <svg
            class="h-5 w-5 group-aria-pressed:hidden"
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
        >
            <path
                d="M2.75 12s3.25-5.25 9.25-5.25S21.25 12 21.25 12 18 17.25 12 17.25 2.75 12
                    2.75 12Z"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linejoin="round"
            />
            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
        </svg>
        <svg
            class="h-5 w-5 hidden group-aria-pressed:block"
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
        >
            <path
                d="m4 4 16 16"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
            />
            <path
                d="M10.25 6.93A8.7 8.7 0 0 1 12 6.75c6 0 9.25 5.25 9.25 5.25a15.8 15.8 0 0
                    1-2.1 2.72M6.3 8.16C3.94 9.66 2.75 12 2.75 12S6 17.25 12 17.25c1.12 0
                    2.13-.18 3.02-.48"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M9.7 9.7a3.25 3.25 0 0 0 4.6 4.6"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
            />
        </svg>
    </button>
</div>
