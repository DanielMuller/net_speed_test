Net Speed Test
==============

Allows to test connexion speed to various parts of the world. To be client-platform independant, the tests uses Javascript to perform the downloads.

To ease the use of XHR, jQuery is used. Results can be loggued and mailed, saving some troubles to the visitor of the page.

Requirements
------------
- PHP
- Servers to host the testfile.bin (S3 is the easiest choice)
- Ability to define CORS on those files (S3 is the easiest choice)

Installation
------------
*S3*

- Create a bucket in the regions you whish to make tests
- Push your file to the bucket
- Authorize XHR to access, by editing the CORS config:
- Make the files public viewable

**S3 CORS**

    <?xml version="1.0" encoding="UTF-8"?>
    <CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
        <CORSRule>
            <AllowedOrigin>*</AllowedOrigin>
            <AllowedMethod>GET</AllowedMethod>
            <AllowedMethod>HEAD</AllowedMethod>
            <MaxAgeSeconds>3000</MaxAgeSeconds>
            <AllowedHeader>Authorization</AllowedHeader>
            <AllowedHeader>Content-*</AllowedHeader>
            <AllowedHeader>Host</AllowedHeader>
        </CORSRule>
    </CORSConfiguration>

**S3 Bucket Policy**

    {
        "Version": "2008-10-17",
        "Statement": [
            {
                "Sid": "AddPerm",
                "Effect": "Allow",
                "Principal": {
                    "AWS": "*"
                },
                "Action": "s3:GetObject",
                "Resource": "arn:aws:s3:::bucketname/*"
            }
        ]
    }

Configuration
-------------
Create *speed.ini*

<code>[notify]</code> is a special block containing notification informations.

All other blocks contains region name and url definition

<code>cdn=true</code> will not count first load of file, in order to allow CDN to have a cached version.

    [notify]
        email=notify@example.com
        from=noreply@example.com
        from_name=Speed Test
    [region1_name]
        url=http://region1-server/testfile.bin
        cdn=false
    [region2_name]
        url=http://region2-server/testfile.bin
        cdn=false

Credits
-------

All the Javascript heavy lifting was made by Johan Andersson for his Nettest project: from https://github.com/anderssonjohan/Nettest
