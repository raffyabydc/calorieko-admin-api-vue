<x-mail::message>
# Welcome to the CalorieKo Team, {{ $admin->name }}!

Your moderator account has been successfully created. You can now access the CalorieKo Admin Dashboard to manage system data.

<x-mail::panel>
**Your Credentials:**
*   **Login URL:** [{{ config('app.url') }}]({{ config('app.url') }})
*   **Email:** {{ $admin->email }}
*   **Initial Password:** `{{ $password }}`
</x-mail::panel>

**Important Security Note:**
Please log in as soon as possible and change your password from the "Account Settings" section to ensure your account remains secure.

<x-mail::button :url="config('app.url')">
Go to Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
