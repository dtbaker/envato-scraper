envato-scraper
==============


This simple PHP class scrapes the users items pages, caches them locally, and then processes them using simple regex to pull out all the users items into a nice PHP array.

Example Usage:

```php
// return all dtbaker items from ThemeForest and CodeCanyon
require_once 'class.envato_scraper.php';
$my_scraper = new envato_scraper();
$my_scraper->do_login('username','password');
$statement = $my_scraper->get_statement('1/2013');
$items = $my_scraper->get_users_items('dtbaker',array('codecanyon','themeforest')); // doesn't work with debug enabled.

echo "My Statement: \n";
print_r($statement);
echo "My Items: \n";
print_r($items);
```

Example Output:
<pre>
My Statement:
Array
(
    [0] => Array
        (
            [type] => sale
            [date] => 2013-01-31 23:43:06 +1100
            [time] => 1359636186
            [item] => WordPress Email Ticket Support Plugin
            [item_id] => 254823
            [envato_item_id] => 0
            [earnt] => 15.40
            [amount] => 22.00
            [rate] => 70.0
        )

    [1] => Array
        (
            [type] => sale
            [date] => 2013-01-31 22:16:25 +1100
            [time] => 1359630985
            [item] => PHP Search Engine
            [item_id] => 89499
            [envato_item_id] => 0
            [earnt] => 7.00
            [amount] => 10.00
            [rate] => 70.0
        )
        etc.....
My Items:
Array
(
    [0] => Array
        (
            [item_id] => 2621629
            [preview_image] => http://1.s3.envato.com/files/30243603/preview-ucm-pro_renew-invoices_pdf_customer-database_emails.jpg
            [cost] => 60
            [sales] => 53
            [name] => Ultimate Client Manager - Pro Edition
            [category] => Php-scripts / Project-management-tools
            [thumb_image] => http://0.s3.envato.com/files/30243602/thumb-ucm-pro_open-source-php-database.png
            [url] => http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629
            [marketplace] => http://codecanyon.net
        )

    [1] => Array
        (
            [item_id] => 2616958
            [preview_image] => http://2.s3.envato.com/files/30196302/preview-customer-job-discussion-project-management-plugin.jpg
            [cost] => 6
            [sales] => 7
            [name] => UCM Plugin: Project Discussion / Customer Comments
            [category] => Php-scripts / Project-management-tools
            [thumb_image] => http://3.s3.envato.com/files/30196301/thumb-customer-project-comments-and-discussion.png
            [url] => http://codecanyon.net/item/ucm-plugin-project-discussion-customer-comments/2616958
            [marketplace] => http://codecanyon.net
        )

    [2] => Array
        (
	etc...
</pre>
