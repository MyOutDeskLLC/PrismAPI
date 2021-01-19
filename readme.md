# PrismHR API

A temporary repo for PrismHR API. The following is a guide.

### Initial Requirements
The following information:
1. `PEO ID` Get this from the system parameters page (`back office -> System -> Change -> System Parameters`)
2. `Web Service User` System -> Change -> System Parameters -> Action Menu -> "Web Service Users". These credentials will be used when calling the API.
3. `ClientID (Company Id)` This is needed from the frontend. Can be found on the My Company dashboard. All employees must be under
this company.

#### Authenticating
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// Throws Authentication Exception if failure occurs
$api->authenticate('WEB_USER_USERNAME','WEB_USER_PASSWORD','PEO_ID');
$api->getSession();
```

#### Getting All Employees
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// Authenticate here...
$employees = $api->getAllEmployees('1111');
```

#### Getting One Or More Employees
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// Authenticate here...
$employee = $api->getEmployee('F72609', '1111');
// Or get multiple
$arrayOfEmployee = $api->getEmployees(['F72609','D68213'], '1111');
```

#### Creating Payroll Batch
A payroll batch is needed to upload timesheets. Use the findOrCreatePayrollBatch to do this. A list of employees MUST be set.
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// Create a payroll batch for a list of employees
$api->findOrCreatePayrollBatch(new \DateTime('now', new \DateTimeZone('UTC')), 'COMPANY_ID', ['EMPLOYEE_ID']);
```

### Creating MyTimeIn Payroll Import Definition
In order to upload timesheets, a payroll import definition must be specified via the backend menu `System` - `Change` - `Pay Import Definition`. The following must be set:

`Definition ID:` MyTimeIn

`Description:` MyTimeIn Import

`File Format:` Delimited

`Delimiter:` Comma

**Import Field Definitions**
Configure the import field definitions like so:

| Field # or Start,End POS | Field Name                             | Conversion Mask |
|--------------------------|----------------------------------------|-----------------|
| 1                        | $EMPID = Employee ID                   |                 |
| 2                        | $DATE = Date Worked Or Period End Date |                 |
| 3                        | $HOURSPOS                              |                 |
| 4                        | $CODEPOS                               |                 |

MyTimeIn will communicate with Prism using REG or OT in for the CODE column.

**The company used to configure MyTimeIn must have access to this pay import definition**

### Uploading Timesheets
Uploading timesheets has a particular flow to it in order to function. The following must be done, in order:

#### Building CSV Data
Built out data to send to Prism with: EMPID, DATE (MM/DD/YYYY), HRS, "REG" or "OT" in CSV format. A CSV helper is bundled
to assist with this process:
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
$csv = $api->getCsvService();
// Example Data
$dataToWrite =[
  ['X1', "12/07/2020", 8, "REG"],
  ['X2', "12/06/2020", 8, "REG"],
  ['X3', "12/05/2020", 8, "REG"],
  ['X4', "12/08/2020", 8, "REG"]
];
// Make a string version
$timesheetData = $csv->createFromData($dataToWrite);
```

#### Make a Payroll Batch
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// authenticate here
$now = new DateTime('now', new DateTimeZone('UTC'));
$payrollBatchId = $api->findOrCreatePayrollBatch($now, 'COMPANY_ID', ['X1', 'X2', 'X3', 'X4']);
```

#### Upload Timesheets Into Batch
```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// auth, etc...
// Process the upload
$timesheetUpload = $api->uploadTimesheets('BATCHID', 'COMPANY_ID', 'USERNAME', 'CSV_STRING_OF_DATA_HERE');
// Make sure to store the key aka the uploadId
$uploadId = $timesheetUpload->getUploadKey();
// If you don't, fetch it from here as it is in progress and will be in the array of batches
$pendingBatches = $api->getTimesheetParamData('COMPANY_ID');
// If there are any errors, handle them here and keep retrying this step of uploading timesheets
if($timesheetUpload->hasErrors()) {
    return json_encode($timesheetUpload->getErrors());
}
```

#### Approve Upload
Once the timesheets response is error free, then you can approve the upload.

```php
$api = new \Myoutdesk\PrismApi\PrismApi();
// auth, etc...
$api->approveTimesheetUpload('TIMESHEETUPLOAD_KEY', '1111', 'username', 'TIMESHEET_UPLOAD_KEY');
```

#### After Approval
After approving the upload of the batch, the entries will appear under `Payroll`-`Action`-`Time Sheet Entry` in PrismHR for an approval from the end user.

