<?php

	function UpdateTemplate($templateFile, $outputFile, $templateVariables)
	{
		$content = file_get_contents($templateFile);
		
		// upgrade template variables
		foreach($templateVariables as $key => $value)
		{
			$content = str_replace('{'. $key . '}', $value, $content);
		}
		
		file_put_contents($outputFile, $content);
	}

	function print_log($message)
	{
		echo $message . PHP_EOL;
	}
	
	function log_error($message)
	{
		echo 'ERROR : ' . $message . PHP_EOL;
		die();
	}

	function exec_system($command)
	{
		echo $command . PHP_EOL;
		
		$result = 0;
		$output = array();
		
		$output = system($command, $result);
		
		if($result !== 0)
		{
			echo $output;
			log_error('execution of command ' . $command . ' failed');
		}
	}
	
	function exec_copy($source, $dest)
	{
		if(!copy($source, $dest))
		{
			log_error('Cannot copy file ' . $source . ' to ' . $dest);
		}
	}
	
	function move_chdir($path)
	{
		echo "cd " . $path . PHP_EOL;
		chdir($path);
	}

	global $argc, $argv;
	
	$nuspecTemplate = "ActionBarSherlock.Standalone.nuspec";
	$projectName = "ActionBarSherlock.Standalone";
		
	if($argc < 2)
	{
		echo "Usage : release.sh <version>";
		die;
	}
	
	$versionNumber = $argv[$argc - 1];
		
	$nugetDirectory = $versionNumber . "/";
	
	if(!file_exists($nugetDirectory))
	{
		log_error("Missing directory " . $nugetDirectory . " to create nuget package");
	}
		
	print_log("# Ok let's go !");
	print_log("# Updating nuspec file for " . $projectName . " in version " . $versionNumber);
	
	$nuspecFile = $nugetDirectory . $nuspecTemplate;
	UpdateTemplate($nuspecTemplate, $nuspecFile, array('version' => $versionNumber));
			
	move_chdir($nugetDirectory);
		
	print_log("# Generating nuget package for " . $projectName . " in version " . $versionNumber);
	exec_system("nuget pack -Verbosity quiet -NonInteractive");
		
	print_log("# Uploading nuget package " . $projectName . " version " . $versionNumber . " to nuget server");
	exec_system("nuget push -Verbosity quiet -NonInteractive *.nupkg");
		
	move_chdir('..');
	
	print_log("# Add new packages to git");
	exec_system("git add -f " . $versionNumber . "/*.nupkg");
	$commitMessage = "Release nuget package " . $projectName . " in version " . $versionNumber;
	$commitMessage = str_replace('"', '\\"', $commitMessage);
	
	print_log("# Commit packages to git");
	
	$tag = 'v_' . $versionNumber;
	exec_system('git tag -a ' . $tag . ' -m "Release ' . $projectName . ' in version ' . $versionNumber . '"');
	exec_system('git commit -m "' . $commitMessage . '"');
	
	// Push all tags
	print_log("# Pushing tags to remote");
	exec_system('git push origin ' . $tag);
	
	print_log("# Finished Release ! :)");
	
	