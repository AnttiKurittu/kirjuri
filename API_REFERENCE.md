# Kirjuri API

# THIS API REFERENCE IS VERY INCOMPLETE. PLEASE READ THE API SOURCE CODE IF YOU REALLY NEED TO IMPLEMENT THIS *NOW*.

Kirjuri has a rudimentary API you can use to read, write and update cases and devices. The API works over HTTP requests and returns results as JSON objects. Using the API requires an API key, which is derived from the username and password of each individual user. Changing the user's password changes their API key, so that needs to be updated accordingly.

The API request uses GET to pass parameters and POST to pass data. Valid GET parameters are:

```
Parameter     Description     Values
id            Case ID         Case or device UNIQUE ID.
operation     Operation       "add" Add data
                              "get" Get data on case or device
                              "update" Update data
                              "info" Get all cases and devices
key           User API key    40 character hash value
year          Year to use     Year in four character format, default current
```


# Add data

Example request for creating a new case:
```
http://localhost/api.php?key=YOURAPIKEY&operation=A
```

Possible POST fields:

* case_name, case_file_number
* case_investigator
* case_investigator_unit
* case_investigator_tel
* case_investigation_lead
* case_confiscation_date
* forensic_investigator
* phone_investigator
* case_crime
* classification
* case_suspect
* case_request_description
* case_urgency
* case_urg_justification
* case_requested_action
* case_contains_mob_dev
