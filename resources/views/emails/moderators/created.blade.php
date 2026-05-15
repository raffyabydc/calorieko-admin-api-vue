<x-mail::message>
<div style="text-align: center; margin-bottom: 20px;">
<img src="{{ config('app.url') }}/images/logo.svg" alt="CalorieKo Logo" style="width: 80px; height: auto;">
</div>

# Welcome to the CalorieKo Team, {{ $admin->name }}!

Hello! Your moderator account has been successfully created. You can now access the CalorieKo Admin Dashboard to manage system data and nutritional records.

<x-mail::panel>
### Your Access Credentials
*   **Login URL:** [{{ config('app.url') }}]({{ config('app.url') }})
*   **Email:** `{{ $admin->email }}`
*   **Initial Password:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="config('app.url')" color="success">
Access Dashboard
</x-mail::button>

**Security Reminder:**
For your protection, please log in and update your password immediately via the "Account Settings" section.

Best regards,<br>
**The {{ config('app.name') }} Administration**
</x-mail::message>
