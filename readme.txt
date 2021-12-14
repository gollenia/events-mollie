=== Events Manager Pro - Mollie Payments ===
Plugin Name: 		Events Manager Pro - Mollie Payments
Contributors:		DuisterDenHaag
Tags: 				mollie, events manager, iDEAL, SOFORT, Bancontact, Apply Pay, Przelewy24
Donate link: 		https://useplink.com/payment/VRR7Ty32FJ5mSJe8nFSx
Requires at least: 	5.3
Tested up to: 		5.5
Requires PHP: 		7.3
Stable tag: 		trunk
License: 			GPLv2 or later
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html


Add 18 payment methods and 31 currencies in one go! This is the <em>only</em> payment add-on for Events Manager that adds more than 2 payment methods to your website.


== Description ==
> Requires [Events Manager](https://wordpress.org/plugins/events-manager/) (free) & [Events Manager Pro](https://eventsmanagerpro.com/) (paid) to be installed & activated.

Let your users pay online using their <em>own</em> favorite payment method. This add-on is extremely easy to setup and use. Easily add up to 18 payment methods and 31 currencies to your Events Manager Pro.
**Mollie is a great alternative to Stipe.**

This easy-to-use payment add-on will automatically add all the activated payment methods in your Mollie profile to Events Manager Pro.


== Features ==
- Customize the Payment Description using Events Manager Placeholders.
- Conditional Payment Description when using Multiple Booking Mode.
- Add 18 payment methods and 31 currencies with just one easy-to-use add-on.
- Show/Hide the activated payment methods on the Booking Form or in a widget.


== Payment Methods ==
Mollie supports the following payment methods:
Apple Pay, Bancontact, Belfius, Cartes Bancaires, all Credit Cards, EPS, Gift Cards, Giropay, iDEAL, ING Home'Pay, KBC, Klarna, PayPal, paysafecard, Postepay, Przelewy24, SEPA & SOFORT.


== Multi Currency ==
Mollie accepts the following currencies:
AED, AUD, BGN, BRL, CAD, CHF, CZK, DKK, EUR, GBP, HKD, HRK, HUF, ILS, ISK, ISK, JPY, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TWD, USD, ZAR.

Please check their docs for more info: [https://docs.mollie.com/payments/multicurrency](https://docs.mollie.com/payments/multicurrency).


== Easy Shortcode ==
Use the shortcode `[mollie_methods]` to display all activated payment methods as a widget or in any post.

== 100% Free Add-on ==
This add-on is free of charge to use. You only pay the low Mollie transaction fees (which are the cheapest ones in The Netherlands).


= Required =
- Latest version of WordPress
- Events Manager + Events Manager Pro
- Free Mollie account [www.mollie.com](https://www.molie.com/)

= Localization =
* English (default) - always included.
* Dutch - always included within the plugin itself.


== Feedback ==
I am very open to your suggestions and feedback!
[Please also check out my other plugins](https://www.stonehengecreations.nl/).


== Installation ==
1. Upload the entire `stonehenge-em-mollie` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Events -> Payment Gateways to enter your personal settings.
4. Activate the gateway in Events -> Payment Gateways.


== Frequently Asked Questions ==
= Are you part of the Events Manager team? =
No, I am not associated with [Events Manager](https://wordpress.org/plugins/events-manager/) nor its developer, [Marcus Sykes](http://netweblogic.com/), in <em>any</em> way.

= Do I really need Events Manager Pro? =
Yes, this plugin adds a Mollie Gateway to Events Manager Pro. It cannot be used without it.

= I don't need 18 payment methods. =
No problem! You can select any of the 18 payments you <em>do</em> want to use in your Mollie Dashboard. This plugin will immediately adjust according to your choices: realtime.

= How do I refund a booking? =
If you, an event owner or a customer cancels a booking, you have to <strong>manually</strong> refund through the Mollie Dashboard or your own bank account. The transaction status is updated to "refunded", but the booking status itself is left unchanged.

= Why are refunds not automated? =
To be honest, it is rather complicated to create automated refunds, because of the different possible scenarios. For example, you may have decided in your Terms & Conditions that a full refund is only possible if certain conditions are met, else only a percentage will be refunded. Also, a refund via Mollie only works if there is a positive balance in your Mollie account. It depends on your Mollie settings when they pay out, emptying your online balance.
There is no (easy) way for this plugin to check all these scenarios, so that is why refunds have to be done manually.

= Why is booking status "cancelled" if there is a charge back? =
When a customer orders a charge back, it is obvious they will not be attending the event. That is why the booking status it automatically set to "cancelled". The email "Cancelled Booking" will also be sent automatically to the user.

= Is this MultiSite compatible? =
Yes, it is. You can set or unset the Mollie Gateway per blog with its own specific settings. You can even use different profiles (with different payment methods) under one Mollie account.

= What options are there? =
* Set a custom payment description using EM Placeholders.
* Mollie checkout page is automatically in your blog's locale.
* It takes a lot of settings right from within Events Manager.
* Show/hide activated payment methods on your Booking Form.
* Show/hide payment status messages (paid, canceled, expired, etc).
* Show/hide Events Manager native feedback messages.
* Redirect to a blank message page, My Bookings or any other page on your blog.

= Can I style it myself? =
Yes. In the current version I only implemented a few lines of inline-styling. Styling classes are either based on Bootstrap or very easy to customize to fit <em>your</em> needs.

= What is Mollie? =
Mollie is a Dutch payment service provider. They give you easy access to a lot of online payment methods at very low fees. Please check [www.mollie.com](https://www.mollie.com) for more details.


== Screenshots ==
1. Overview of available gateway settings.
2. Show activated payment methods on Booking Form.
3. Example of payment confirmation status.
4. Available wildcards for the payment description.
5. Linking the Transaction ID directly to the Mollie Dashboard.


== Upgrade Notice ==


== Changelog ==
= 2.4.4 =
- Added: Support for Quick Payment Buttons. Checkout [Mollie Resources](https://www.mollie.com/nl/resources) for the official Mollie logos.
- Updated Mollie PHP API to version 2.21.0.
- Confirmed compatibility with WordPress 5.5.

= 2.4.3 =
- Some bug fixes.
- Updated to Mollie API version 2.17.0.

= 2.4.2 =
- Confirmed compatibility with WordPress 5.4.
- Confirmed compatibility with PHP 7.4.2.

= 2.4.1 =
- Downgraded to Guzzle version 6.5.0, because some users experienced errors. [See Github Issue](https://github.com/guzzle/guzzle/issues/2511)

= 2.4 =
- **NEW:** Payment Method images are now being loaded through a cached sprite image (faster & better for SEO).
- Bug fix: Partially refunded payment showed as fully refunded.
- Updated to Mollie API version 2.16.0.
- Some cosmetic enhancements.
- Confirmed compatibility with PHP 7.4.

= 2.3 =
- **ADDED:** You can now change the Payment Status Text in the Customer Return Page to your personal liking.
- Bug fix in customer return page always showing the "Pending" feedback message.
- Changed the way the payment status is determined.
- Updated .pot file for translations.
- Updated Dutch translation (included in the download).
- Confirmed compatibility with WordPress 5.3.

= 2.2 =
- Payment Description now supports all Events Manager Placeholders. <em>(The Transactions Table will still show "Multiple Events" in MBM.)</em>
- Better support for Multiple Bookings Mode (also Payment Description).
- Updated .pot file and Dutch translations.

= 2.1 =
- Updated to Mollie API version 2.12.
- New transactions in the Transactions Table will now be translated.
- Confirmed compatibility with WordPress 5.2.4.

= 2.0.1 =
* Very minor bug fix that prevented the return page payment status to be translated.

= 2.0 =
* Better error handling if booking form fields are not filled out or empty.
* Some bug fixes when installing this add-on on PHP 7.2+
* Prevented errors when API key is not entered.
* Code clean-up for future use.

= 1.9.2 =
- Minor bug fix in loading translation files. Customer return page now translates correctly again.
- Code change to prevent "Using $this when not in an Object" in some cases.

= 1.9.1 =
- Minor bug fix due to a translation change in WP 5.2.
- Updated enclosed .pot file.
- Updated Dutch translations file.

= 1.9.0 =
- Upgraded to Mollie API version 2.10.0. Mollie now supports Apple Pay.
- Confirmed compatibility with WordPress 5.2.1


== Translations ==
* English - default, always included.
* Dutch: Nederlands - zit er altijd bij!
