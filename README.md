This is the source code for my [benchmarking tutorial](https://blog.programster.org/mysql-using-barcodes-as-primary-key) on using different MySQL types for a barcode primary key. This was to see whether it was better to take the overhead hit of converting barcodes to/from binary strings and integers. In summary, yes, it does make sense to do so as the number of products grows, however, with small tables it doesn't matter too much.

## Usage
* Spin up a MySQL database.
* Install php 7.0+ cli.
* Edit the defines/settings at the top of src/main.php
* Execute the script with `php src/main.php`
