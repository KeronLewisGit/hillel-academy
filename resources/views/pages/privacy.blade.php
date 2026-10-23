<x-layouts.site title="Privacy notice">
    <x-page-header title="Privacy notice" note="Your information" lead="How Hillel Academy uses the information you give us through this website." />
    <section class="wrap mt-12 md:mt-16">
        <div class="prose-copy measure">
            <p><em>This notice describes how the website works. The school should review it, and add its data-protection contact, before the site goes live.</em></p>
            <h2>What we collect</h2>
            <ul>
                <li><strong>Enquiries and visit bookings:</strong> your name, contact details, your child’s first name and grade, and your message.</li>
                <li><strong>Applications:</strong> the details you enter in the online form, including your child’s date of birth, nationality, schooling and any support needs you choose to share, plus the documents you upload (birth certificate, school reports, immunisation record, passport).</li>
                <li><strong>Teacher applications:</strong> your contact details, experience, qualifications and CV.</li>
            </ul>
            <h2>How it is used</h2>
            <p>Only to answer your enquiry, arrange visits, assess and process applications, and recruit staff. We do not sell your information or use it for advertising.</p>
            <h2>Who can see it</h2>
            <p>Uploaded documents and CVs are stored privately and can only be opened by you (in the family portal) and by signed-in members of the Admissions Office. Staff accounts have roles, so for example the communications team cannot see applications.</p>
            <h2>Signing in</h2>
            <p>The family portal has no password. We email you a link that works once and expires after {{ \App\Models\Family::LINK_MINUTES }} minutes. Asking for a link never reveals whether an email address has an application.</p>
            <h2>Cookies</h2>
            <p>The site uses only the cookies it needs to work: a session cookie and a security token for forms. There are no advertising or tracking cookies. Your light or dark theme choice is kept in your own browser.</p>
            <h2>Your rights</h2>
            <p>You can ask to see, correct or delete the information we hold about you. Contact the Admissions Office at <a href="mailto:{{ setting('email_admissions') }}">{{ setting('email_admissions') }}</a> or {{ format_phone(setting('phone_admissions')) }}.</p>
        </div>
    </section>
</x-layouts.site>
