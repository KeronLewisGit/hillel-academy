<x-mail-layout title="Your sign-in link">
    <p style="margin:0 0 14px">Dear {{ $name }},</p>
    @if ($newApplication)
        <p style="margin:0 0 14px">Thank you for starting an application to Hillel Academy. Use the button below to continue. Your answers save as you go, so you can stop and come back at any time: just ask for a new link from the family portal.</p>
    @else
        <p style="margin:0 0 14px">Here is your link to the Hillel Academy family portal, where you can continue an application, upload documents, book an assessment and see decisions.</p>
    @endif
    <p style="margin:22px 0"><a href="{{ $url }}" style="display:inline-block;background:#14213d;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:4px">Open the family portal</a></p>
    <p style="margin:0 0 14px;font-size:14px;color:#4d5a73">The link works once and expires in {{ \App\Models\Family::LINK_MINUTES }} minutes. If you did not ask for it, you can ignore this email.</p>
</x-mail-layout>
