import { siteConfig } from '@/config/site';

export default function PrivacyPolicyPage() {
  return (
    <div className="min-h-screen bg-surface-bg">
      {/* Header */}
      <header className="bg-white border-b border-border-light">
        <div className="mx-auto max-w-[1200px] px-5 sm:px-6 lg:px-8 h-[72px] flex items-center">
          <a href="/" className="flex items-center gap-2.5 shrink-0">
            <img src="/branding/logo.png" alt="Tocco Voice Live" className="h-9 w-9 rounded-[12px] object-contain" />
            <span className="text-[17px] font-bold text-brand tracking-tight">
              {siteConfig.name}
            </span>
          </a>
        </div>
      </header>

      {/* Main content */}
      <main className="mx-auto max-w-[800px] px-5 sm:px-6 py-12 sm:py-16 lg:py-20">
        <a
          href="/"
          className="inline-flex items-center gap-2 text-[14px] text-text-muted hover:text-brand transition-colors mb-8"
        >
          &larr; Back to website
        </a>
        
        <div className="bg-white rounded-3xl border border-border-light shadow-[0_1px_3px_rgba(0,0,0,0.04)] p-8 sm:p-10 md:p-12">
          <h1 className="text-[2rem] sm:text-[2.5rem] font-bold text-text mb-4 tracking-tight">Tocco Voice Chat Privacy Policy</h1>
          <p className="text-slate-500 font-semibold mb-8">Last Updated: September 2026</p>

          <div className="space-y-6 text-[0.95rem] text-slate-600 leading-relaxed">
            <p>At Tocco Voice Chat (&quot;Tocco&quot;, &quot;we&quot;, &quot;us&quot;, or &quot;our&quot;), we respect your privacy and are committed to protecting the personal information of our users. This Privacy Policy explains how we collect, use, disclose, store, and protect information when you use the Tocco Voice Chat mobile application, website, and related services (collectively, the &quot;Services&quot;).</p>
            <p>Tocco is a social voice communication platform that allows users to participate in voice chat rooms, communicate privately with other users, discover and connect with people, participate in interactive entertainment features, and use other community and communication features.</p>
            <p>By accessing or using the Services, you acknowledge that you have read and understood this Privacy Policy.</p>
            
            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">Contents</h2>
            <ul className="list-disc pl-5 space-y-2 mb-8">
              <li>What Information We Collect</li>
              <li>How We Use Your Information</li>
              <li>Voice Chat and User-Generated Content</li>
              <li>How We Share Your Information</li>
              <li>Third-Party Services</li>
              <li>International Data Transfers</li>
              <li>Cookies and Similar Technologies</li>
              <li>Data Security</li>
              <li>Your Choices and Controls</li>
              <li>Your Rights</li>
              <li>Children and Age Requirements</li>
              <li>Data Retention</li>
              <li>Account Deletion</li>
              <li>Changes to This Privacy Policy</li>
              <li>Contact Us</li>
            </ul>

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">1. What Information We Collect</h2>
            <p>We collect information that you provide directly to us, information generated when you use our Services, and information provided by third-party services when you choose to connect them to your Tocco account.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">1.1 Information You Provide</h3>
            <p>Depending on how you use Tocco, we may collect the following information:</p>

            <h4 className="text-[1.1rem] font-bold text-text mt-6 mb-2">Account Information</h4>
            <p>When you create or use a Tocco account, we may collect information such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>User ID or account ID</li>
              <li>Phone number or email address, where applicable</li>
              <li>Login and authentication information</li>
              <li>Date of birth or age information</li>
              <li>Country or region</li>
              <li>Preferred language</li>
              <li>Account creation and account status information</li>
            </ul>

            <h4 className="text-[1.1rem] font-bold text-text mt-6 mb-2">Profile Information</h4>
            <p>You may choose to provide information for your Tocco profile, including:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Display name or nickname</li>
              <li>Profile photograph</li>
              <li>Gender, where provided</li>
              <li>Personal introduction or bio</li>
              <li>Level, badges, achievements, and other profile-related information</li>
              <li>Other information that you voluntarily add to your profile</li>
            </ul>
            <p>Some profile information may be visible to other users depending on your privacy settings and the nature of the feature.</p>

            <h4 className="text-[1.1rem] font-bold text-text mt-6 mb-2">User-Generated Content</h4>
            <p>When you use Tocco, you may create or transmit content, including:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Voice messages</li>
              <li>Voice-room communications</li>
              <li>Text messages</li>
              <li>Comments</li>
              <li>Profile content</li>
              <li>Images or photographs</li>
              <li>Room names, descriptions, and other room-related content</li>
              <li>Reports, complaints, feedback, and support communications</li>
            </ul>
            <p>We may process this information as necessary to provide the Services, maintain safety, enforce our policies, and respond to reports or complaints.</p>

            <h4 className="text-[1.1rem] font-bold text-text mt-6 mb-2">Transaction and Virtual Item Information</h4>
            <p>If Tocco provides purchases, virtual coins, diamonds, gifts, subscriptions, or other paid features, we may process information associated with those transactions, including:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Purchase history</li>
              <li>Virtual currency balances and transactions</li>
              <li>Gifts and rewards</li>
              <li>Subscription information</li>
              <li>Transaction identifiers</li>
              <li>Payment status</li>
            </ul>
            <p>Payment card or other sensitive payment credentials may be processed by the applicable payment provider rather than being directly stored by Tocco.</p>

            <h4 className="text-[1.1rem] font-bold text-text mt-6 mb-2">Communications With Us</h4>
            <p>If you contact our support team, we may collect:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Your contact information</li>
              <li>Your account information</li>
              <li>Your messages and communications with support</li>
              <li>Information about the issue you report</li>
              <li>Attachments or other information you choose to provide</li>
            </ul>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">2. Information We Collect Automatically</h2>
            <p>When you use Tocco, certain information may be collected automatically.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Device and Technical Information</h3>
            <p>This may include:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Device type and model</li>
              <li>Operating system and version</li>
              <li>App version</li>
              <li>Device identifiers</li>
              <li>IP address</li>
              <li>Network information</li>
              <li>Mobile carrier information</li>
              <li>Language and regional settings</li>
              <li>Time zone</li>
              <li>Crash and diagnostic information</li>
            </ul>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Usage Information</h3>
            <p>We may collect information about how you interact with Tocco, such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Features you use</li>
              <li>Voice rooms you join or create</li>
              <li>General activity within the application</li>
              <li>Interactions with other users</li>
              <li>Messages and communication activity</li>
              <li>Game or entertainment feature usage</li>
              <li>Purchases and virtual-item activity</li>
              <li>Reports and moderation activity</li>
              <li>Dates and times of activity</li>
            </ul>
            <p>We use this information to operate, secure, maintain, analyze, and improve Tocco.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Approximate Location Information</h3>
            <p>Where permitted and where necessary for a feature, we may process approximate location information derived from information such as your IP address or device settings.</p>
            <p>If Tocco requests precise device location permission for a particular feature, you can control that permission through your device settings.</p>
            <p>Tocco does not require you to provide precise location information unless a particular feature specifically requires it and you choose to enable that feature.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">3. Voice Chat and User-Generated Content</h2>
            <p>Voice communication is a core feature of Tocco.</p>
            <p>When you participate in voice rooms or private voice communication, your voice is transmitted through the Services to enable communication with other users.</p>
            <p>Depending on the feature and applicable technical requirements, certain voice or communication data may be temporarily processed by Tocco and/or our service providers for purposes such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Delivering real-time voice communication</li>
              <li>Maintaining service functionality</li>
              <li>Detecting technical problems</li>
              <li>Preventing abuse and misuse</li>
              <li>Investigating reports</li>
              <li>Enforcing our Terms and Community Guidelines</li>
              <li>Protecting users and the integrity of the platform</li>
            </ul>
            <p>Tocco does not use private communications for advertising purposes.</p>
            <p>Users should understand that information they voluntarily share in public or group voice rooms may be heard by other participants. You should avoid sharing sensitive personal information such as passwords, financial information, home addresses, or other information you do not want other users to know.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">4. How We Use Your Information</h2>
            <p>We use information collected through Tocco for purposes including:</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Providing and Operating the Services</h3>
            <p>We use information to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Create and maintain user accounts</li>
              <li>Authenticate users</li>
              <li>Provide voice chat rooms and private communication</li>
              <li>Enable messaging and social features</li>
              <li>Provide entertainment and interactive features</li>
              <li>Maintain user profiles, levels, badges, coins, diamonds, gifts, and other platform features</li>
              <li>Process transactions where applicable</li>
              <li>Provide customer support</li>
            </ul>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Improving Tocco</h3>
            <p>We may use information to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Understand how users interact with Tocco</li>
              <li>Analyze application performance</li>
              <li>Improve existing features</li>
              <li>Develop new features</li>
              <li>Fix bugs and technical issues</li>
              <li>Improve stability and reliability</li>
              <li>Personalize certain aspects of the user experience</li>
            </ul>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Safety, Security, and Moderation</h3>
            <p>We may use information to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Detect and prevent fraudulent activity</li>
              <li>Detect spam, abuse, harassment, and other prohibited behavior</li>
              <li>Investigate reports submitted by users</li>
              <li>Enforce our Terms of Service and Community Guidelines</li>
              <li>Protect users and our platform</li>
              <li>Prevent unauthorized access</li>
              <li>Detect security incidents</li>
              <li>Take action against accounts that violate our policies</li>
            </ul>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Legal and Regulatory Compliance</h3>
            <p>We may process information when reasonably necessary to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Comply with applicable laws</li>
              <li>Respond to valid legal requests</li>
              <li>Cooperate with law enforcement where legally required</li>
              <li>Protect our legal rights</li>
              <li>Investigate suspected illegal activity</li>
              <li>Enforce our agreements and policies</li>
            </ul>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">5. How We Share Your Information</h2>
            <p>We do not sell your personal information to third parties.</p>
            <p>We may share information in the following circumstances.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">5.1 With Other Users</h3>
            <p>Certain information is shared with other users as part of the normal operation of Tocco.</p>
            <p>For example, depending on your settings and the feature being used, other users may see:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Your nickname</li>
              <li>Profile photograph</li>
              <li>Level and badges</li>
              <li>Public profile information</li>
              <li>Information you voluntarily post</li>
              <li>Messages or content you send to them</li>
              <li>Content shared in public or group rooms</li>
            </ul>
            <p>You should carefully consider what information you choose to share with other users.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">5.2 Service Providers</h3>
            <p>We may use trusted third-party service providers to help operate Tocco.</p>
            <p>These providers may support services such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Cloud hosting</li>
              <li>Data storage</li>
              <li>Voice communication infrastructure</li>
              <li>Authentication</li>
              <li>Analytics</li>
              <li>Crash reporting</li>
              <li>Security</li>
              <li>Customer support</li>
              <li>Payment processing</li>
              <li>Notifications</li>
              <li>Technical infrastructure</li>
            </ul>
            <p>These providers may access information only as reasonably necessary to provide services to us and are expected to protect that information.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">5.3 Legal and Safety Requirements</h3>
            <p>We may disclose information when we reasonably believe disclosure is necessary to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Comply with applicable law or legal process</li>
              <li>Respond to lawful requests from government authorities</li>
              <li>Protect the safety of users</li>
              <li>Investigate fraud or abuse</li>
              <li>Investigate violations of our policies</li>
              <li>Protect Tocco, our employees, users, or other parties</li>
              <li>Prevent or address security or technical problems</li>
            </ul>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">5.4 Business Transfers</h3>
            <p>If Tocco or substantially all of its assets are involved in a merger, acquisition, restructuring, financing, sale, or other business transaction, personal information may be transferred as part of that transaction, subject to applicable law.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">6. Third-Party Services</h2>
            <p>Tocco may use third-party technologies and services to provide, maintain, secure, and improve the application.</p>
            <p>These third parties may process information according to their own privacy policies and contractual obligations.</p>
            <p>Examples may include services used for:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Cloud infrastructure</li>
              <li>Authentication</li>
              <li>Voice communication</li>
              <li>Analytics</li>
              <li>Application performance monitoring</li>
              <li>Payment processing</li>
              <li>Push notifications</li>
              <li>Security and fraud prevention</li>
            </ul>
            <p>When you interact directly with a third-party service, that service may collect information according to its own privacy policy.</p>
            <p>We encourage you to review the privacy policies of third-party services that you choose to use.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">7. International Data Transfers</h2>
            <p>Your information may be processed or stored in countries other than the country where you live.</p>
            <p>Where information is transferred internationally, we take reasonable measures designed to protect your personal information and comply with applicable privacy and data-protection requirements.</p>
            <p>By using the Services, you understand that your information may be processed in jurisdictions where privacy laws may differ from those in your country.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">8. Cookies and Similar Technologies</h2>
            <p>Our website and certain parts of our Services may use cookies, local storage, SDKs, pixels, and similar technologies.</p>
            <p>These technologies may be used to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Keep you signed in</li>
              <li>Remember preferences</li>
              <li>Maintain website functionality</li>
              <li>Understand how the Services are used</li>
              <li>Measure performance</li>
              <li>Detect security issues</li>
              <li>Improve the user experience</li>
            </ul>
            <p>You may be able to control cookies through your browser settings. Disabling certain cookies may affect the functionality of some website features.</p>
            <p>Mobile devices may also provide privacy controls that allow you to restrict certain tracking or advertising technologies.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">9. Data Security</h2>
            <p>We take reasonable technical, administrative, and organizational measures designed to protect personal information against unauthorized access, alteration, disclosure, misuse, or destruction.</p>
            <p>Depending on the nature of the information and the Services involved, security measures may include:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Access controls</li>
              <li>Authentication mechanisms</li>
              <li>Encryption or secure transmission technologies</li>
              <li>Monitoring and logging</li>
              <li>Security controls for infrastructure</li>
              <li>Restricted access to personal information</li>
            </ul>
            <p>However, no internet-based service can guarantee absolute security.</p>
            <p>You are responsible for maintaining the confidentiality of your account credentials and should immediately contact us if you believe your account has been compromised.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">10. Your Choices and Controls</h2>
            <p>You have choices regarding certain information and features.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Device Permissions</h3>
            <p>You can control permissions such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Microphone</li>
              <li>Camera, where applicable</li>
              <li>Notifications</li>
              <li>Location, where applicable</li>
              <li>Other device permissions</li>
            </ul>
            <p>These permissions can generally be managed through your device settings.</p>
            <p>Please note that disabling certain permissions may prevent associated Tocco features from working correctly.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Profile Information</h3>
            <p>You may be able to update or remove certain profile information directly within the application.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Notifications</h3>
            <p>You can control push notifications through your device's notification settings.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Privacy and Communication Controls</h3>
            <p>Where available, you can use Tocco's privacy and account settings to manage certain interactions and communication preferences.</p>

            <h3 className="text-[1.2rem] font-bold text-text mt-8 mb-3">Account Information</h3>
            <p>You may request access to, correction of, or deletion of certain information associated with your account, subject to applicable law and legitimate operational or legal requirements.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">11. Your Privacy Rights</h2>
            <p>Depending on where you live and applicable law, you may have rights regarding your personal information, including the right to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Request access to personal information we hold about you</li>
              <li>Request correction of inaccurate information</li>
              <li>Request deletion of your personal information</li>
              <li>Request restriction of certain processing</li>
              <li>Object to certain processing</li>
              <li>Request portability of certain information</li>
              <li>Withdraw consent where processing is based on consent</li>
            </ul>
            <p>Some rights may be subject to legal or other limitations.</p>
            <p>To exercise applicable privacy rights, contact us using the contact information provided below.</p>
            <p>We may need to verify your identity before processing certain requests.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">12. Children and Age Requirements</h2>
            <p>Tocco Voice Chat is intended for users who meet the minimum age requirement applicable to the Services.</p>
            <p><strong>Tocco does not knowingly permit children under the applicable minimum age to use the Services.</strong></p>
            <p>Where required, we may collect age or date-of-birth information to help determine whether a user is eligible to use Tocco.</p>
            <p>If we become aware that we have collected personal information from a child who is not permitted to use the Services, we will take reasonable steps to delete the information and terminate the associated account, subject to applicable legal requirements.</p>
            <p>If you believe that a child has created an account or provided personal information to Tocco in violation of our requirements, please contact us.</p>
            <p>Tocco also provides reporting and moderation mechanisms to help users report inappropriate content, abusive behavior, exploitation, or other safety concerns.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">13. Data Retention</h2>
            <p>We retain personal information for as long as reasonably necessary to provide the Services and for legitimate business, safety, security, fraud-prevention, and legal purposes.</p>
            <p>The length of time we retain information depends on factors such as:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>The type of information</li>
              <li>The purpose for which it was collected</li>
              <li>Whether your account remains active</li>
              <li>Legal and regulatory requirements</li>
              <li>Security and fraud-prevention requirements</li>
              <li>The resolution of disputes or complaints</li>
            </ul>
            <p>When information is no longer required, we take reasonable steps to delete or anonymize it, subject to applicable legal requirements.</p>
            <p>Certain information may need to be retained for longer periods where required or permitted by law, or where reasonably necessary to prevent fraud, abuse, or repeated violations of our policies.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">14. Account Deletion</h2>
            <p>You may request deletion of your Tocco account through the account-management functionality provided within the application.</p>
            <p>Where available, the account deletion process may be accessed through:</p>
            <p className="font-bold">Me / Profile &rarr; Settings &rarr; Account &rarr; Delete Account</p>
            <p>When an account deletion request is submitted, we will process the request in accordance with applicable law and our account-deletion procedures.</p>
            <p>Deletion may result in the permanent loss of:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Your account</li>
              <li>Profile information</li>
              <li>Messages and other account-associated content</li>
              <li>Virtual items or balances</li>
              <li>Levels, badges, achievements, and other account progress</li>
              <li>Other information associated with your account</li>
            </ul>
            <p>Certain information may be retained where required or permitted by law or where reasonably necessary for legitimate purposes such as fraud prevention, security, dispute resolution, or compliance with legal obligations.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">15. Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time to reflect changes to:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>Tocco&apos;s Services</li>
              <li>Our data practices</li>
              <li>Applicable laws and regulations</li>
              <li>Security and privacy requirements</li>
            </ul>
            <p>When we make material changes, we may provide notice through the application, website, or other appropriate means.</p>
            <p>The &quot;Last Updated&quot; date at the beginning of this Privacy Policy indicates when the policy was most recently revised.</p>
            <p>Your continued use of Tocco after an updated Privacy Policy becomes effective means that you acknowledge the revised policy, subject to applicable law.</p>

            <hr className="my-8 border-border-light" />

            <h2 className="text-[1.5rem] font-bold text-text mt-10 mb-4">16. Contact Us</h2>
            <p>If you have questions, concerns, privacy requests, or complaints regarding this Privacy Policy or Tocco&apos;s handling of personal information, please contact us:</p>
            <p><strong>Tocco Voice Chat</strong></p>
            <p><strong>Email:</strong> [YOUR PRIVACY/ SUPPORT EMAIL]</p>
            <p><strong>Website:</strong> [YOUR OFFICIAL WEBSITE]</p>
            <p>For account deletion requests, privacy requests, or questions regarding your personal information, please include sufficient information for us to identify your account and process your request securely.</p>

            <hr className="my-8 border-border-light" />

            <p className="font-bold">&copy; 2026 Tocco Voice Chat. All rights reserved.</p>

          </div>
        </div>
      </main>
    </div>
  );
}
