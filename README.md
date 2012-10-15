Why?
	Basicaly because of crossbrowser compatibility. In fact it is supported by any* browser.
	
How to use?	
	$p = new FileUploadProgress("unique_file_description");
	// ... insert in upload form before file field
	echo $p->getHiddenUploadFieldHTML();
	// and now get upload progress info
	$arrayWithThings = $p->getProgress(); // this will throw an exception if no/wrong data
	
Please note!!!
	File upload track should be done using ajax or frames, php script with file data(from $_FILES) will be available
	after upload only, and this class make no sense if used in that script.
	
What to install before to get it working?	
	- To enable upload track support install and configure apc:
	apc.rfc1867 = 1
	
	- Or to install uploadprogress PHP extension
	
	- Or to install PHP 5.4< and set: session.upload_progress.enabled = 1 ;set by default here
	
	
