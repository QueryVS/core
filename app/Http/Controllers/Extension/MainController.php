<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Script;

class MainController extends Controller
{
    public function allServers()
    {
        $servers = extension()->servers();
        $cities = array_values(objectToArray($servers, "city", "city"));
        if($servers->count() == 1 && !request()->has('noRedirect')){
            return redirect(route('extension_server',[
                "server_id" => $servers->first()->_id,
                "extension_id" => extension()->_id,
                "city" => $cities[0]
            ]));
        }
        if(count($cities) == 1 && !request()->has('noRedirect')){
            return redirect(route('extension_city',[
                "extension_id" => extension()->_id,
                "city" => $cities[0]
            ]));
        }

        return view('extension_pages.index', [
            "cities" => implode(',', $cities)
        ]);
    }

    public function download()
    {
        // Generate Extension Folder Path
        $path = resource_path('views' . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . strtolower(extension()->name));

        // Initalize Zip Archive Object to use it later.
        $zip = new \ZipArchive;

        // Create Zip
        $zip->open('/liman/export/' . extension()->name . '.lmne', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Iterator to search all files in folder.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Create Folder to put views in
        $zip->addEmptyDir('views');

        // Simply, go through files recursively and add them in to zip.
        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($path) + 1);

                // Add current file to archive
                $zip->addFile($filePath, 'views/' . $relativePath);
            }
        }

        // Create Folder to put scripts in
        $zip->addEmptyDir('scripts');

        // Simply, go through scripts and add them in to zip.
        foreach (extension()->scripts() as $script) {
            $zip->addFile(storage_path('app/scripts/') . $script->_id, 'scripts/' . $script->unique_code . '.lmns');
        }

        // Extract database in to json.
        file_put_contents('/liman/export/' . extension()->name . '_db.json', extension()->toJson());

        // Add file
        $zip->addFile('/liman/export/' . extension()->name . '_db.json', 'db.json');

        // Close/Compress zip
        $zip->close();

        // Return zip as download and delete it after sent.
        return response()->download('/liman/export/' . extension()->name . '.lmne')->deleteFileAfterSend();
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function upload()
    {
        // Initalize Zip Archive Object to use it later.
        $zip = new \ZipArchive;

        // Try to open zip file.
        if (!$zip->open(request()->file('extension'))) {
            return respond("Eklenti dosyasi acilamiyor.", 201);
        }

        // Determine a random tmp folder to extract files
        $path = '/tmp/' . str_random(10);
        $zip->extractTo($path);

        // Control to bypass GitHub, GitLab direct zip download.
        if (strpos(request()->file('extension')->getClientOriginalName(), '-master')) {
            $path = $path . '/' . substr(request()->file('extension')->getClientOriginalName(), 0, -4);
        }

        //Now that we have everything, let's extract file.
        $file = file_get_contents($path . '/db.json');
        $json = json_decode($file, true);

        // Create extension object and fill values.
        $new = new \App\Extension();
        $new->fill($json);
        $new->save();

        $extension_folder = resource_path('views/extensions/' . strtolower($json["name"]));

        // Delete existing folder.
        if (is_dir($extension_folder)) {
            $this->rmdir_recursive($extension_folder);
        }

        mkdir($extension_folder);

        // Copy Views into the liman.
        shell_exec('cp -r ' . $path . '/views/* ' . $extension_folder);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/scripts/'),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                if (substr($file->getFilename(), 0, 1) == ".") {
                    continue;
                }

                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                $script = Script::readFromFile($filePath);
                shell_exec('cp ' . $filePath . ' ' . storage_path('app' . DIRECTORY_SEPARATOR . 'scripts') . DIRECTORY_SEPARATOR . $script->_id);
            }
        }

        return respond("Eklenti basariyla eklendi", 200);
    }

    /**
     * @param $dir
     */
    private function rmdir_recursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) $this->rmdir_recursive("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
