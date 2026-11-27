# Sprint 5 defense walkthrough

Run the walkthrough in the separate `devaura_lms_demo` schema. The sample identities, attendance, and loans are synthetic.

## Start the prototype in Laragon

In Laragon, start MySQL and create an empty `devaura_lms_demo` schema. Use PHP 8.4 or newer with PDO MySQL, GD, and Zip enabled. From a Laragon terminal in the Sprint 5 checkout, run `composer install` and create a local `.env` from `.env.example` if needed. Set the following values and enter the local MySQL account credentials for this schema. Port 3307 is the team's Laragon default.

```dotenv
APP_ENV=local
APP_URL=http://localhost:8000
KIOSK_URL=http://127.0.0.1:8001
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=devaura_lms_demo
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=array
MAIL_FROM_ADDRESS=library@example.com
```

Leave `DB_URL` unset. Run `php artisan key:generate` once if `APP_KEY` is blank. Clear cached configuration and prepare the schema.

```powershell
php artisan config:clear
php artisan library:check-database
php artisan migrate
php artisan db:seed --class=SprintFiveDemoSeeder
php artisan schedule:list
composer dev
```

The seeder loads the predefined permissions and reference data. `composer dev` starts the staff and kiosk servers and the default and `member-mail` queue workers. Open a second terminal in the same checkout and run `php artisan schedule:work` to process recurring due and overdue notices. The one-off scan is `php artisan library:circulation-notifications`. The array mailer keeps rehearsal mail in memory.

If the Composer launcher is unavailable, start the following processes in separate terminals with the same demo configuration.

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --tries=1
php artisan serve --host=127.0.0.1 --port=8001 --tries=1
php artisan queue:work --sleep=1
php artisan queue:work member-mail --sleep=1
php artisan schedule:work
```

Open the [staff sign-in](http://localhost:8000/login) and the [public kiosk](http://127.0.0.1:8001/kiosk) in separate browser profiles. Use a third profile or private window for the Student inbox while the Head Librarian stays signed in.

## Sample credentials and copies

All four sample accounts use the password `Sprint5-Demo!Only`.

| Account | Sign-in email | Membership reference |
| --- | --- | --- |
| Head Librarian | `defense.head@example.com` | `9900001` |
| Assigned Teacher | `defense.admin@example.com` | `9900002` |
| Student | `defense.student@example.com` | `990000000001` |
| Student with an overdue loan | `defense.overdue@example.com` | `990000000002` |

| Copy | QR value | Demonstration |
| --- | --- | --- |
| Demo Atlas | `BK-DM01-AA` | Normal checkout and return |
| Demo Field Guide | `BK-DM02-AA` | Damaged return |
| Demo Almanac | `BK-DM03-AA` | Reported loss |
| Demo Loan Primer | `BK-DM04-AA` | Existing overdue loan |
| Demo Local History | `BK-DM05-AA` | Individual inventory discrepancy |

For the walk-in form, use first name `Demo`, last name `Visitor`, email `defense.visitor@example.com`, and purpose `Read reference books`.

## Rehearse the working demo

1. Sign in as the Head Librarian. Confirm the kiosk initially shows no attendance forms and that opening the staff sign-in path on port 8001 redirects to the kiosk. In **Attendance Monitoring**, use **Record Time In**. Refresh the kiosk in its separate profile. Time the Student in using `990000000001`, use **Resume** after each submission, repeat Time In to show it keeps the original visit, then time out. Record the walk-in visitor's Time In and use the same email to time out. Return to Attendance Monitoring to find the closed Student and visitor visits.
2. Open **Dashboard** before running any checkout. On the day of the initial seed, the shared totals start at four available copies, one open loan, and one overdue loan. The attendance series has one Student visit two days before setup, one visitor visit the day before setup, and three visits from step 1. The circulation series has one checkout five days before setup, one checkout three days before, and one return the day before setup. Compare the daily values with the report for those same Manila dates.
3. In **Book Borrowing**, try to check out Demo Atlas to the overdue Student. Confirm the application rejects the request. As that Student, open **Notifications** and confirm there is one overdue notice for Demo Loan Primer after the scan and the attempted checkout. As Head Librarian, verify the loan in the Circulation report under **Open overdue loans by due date** before returning it. Return `BK-DM04-AA` as **Fit for circulation**, then borrow Demo Atlas for the same member to show eligibility is restored. Use **Book Returns** to find the same copy and select **Fit for circulation**. Confirm the copy is available.
4. Check out Demo Field Guide and Demo Almanac to `990000000001`. For the first checkout, use **Search & Select** to locate the member and exact copy. Sign in as that Student and open **Notifications** to show the pending-item alerts. Use **Book Returns** to record **Damaged** for Demo Field Guide and **Reported lost** for Demo Almanac. Confirm both are out of circulation with different catalog states.
5. In **Inventory Audits**, record `BK-DM05-AA` as **Damaged**. The copy starts available, and the individual check records both the prior and resulting conditions. Open **Operational Reports** as the Head Librarian and choose a range from five days before seed through the current date. Review Attendance, Circulation and Inventory. Use the attendance role filter to isolate Student visits. Download the filtered PDF and Excel files from the same results and open both. Check the date range, column labels, matching record count and expected rows. Select an earlier empty range and repeat to see the empty output.
6. Open **Audit Trail**. Locate the sign-in, attendance, circulation, and inventory events just demonstrated. Confirm the relevant entries show the actor, action, and Manila timestamp. Sign in as the Assigned Teacher in a separate profile and confirm access to Operational Reports and Audit Trail. Try `/reports?format=pdf`, `/reports?format=xlsx`, and `/audit-trail` as the Student and confirm access is forbidden.
7. In Attendance Monitoring as the Head Librarian, use **Record Time Out**. Refresh the public kiosk and confirm the forms close. Open the dashboard at desktop and narrow viewport widths. It should show two available copies, zero open loans, zero overdue loans, and three visits today.

The seeder adds each fixture once and preserves status and history from previous rehearsals. For a full repeat from the initial counts, recreate only the dedicated demo schema in Laragon, migrate, and rerun the seeder on rehearsal day.
