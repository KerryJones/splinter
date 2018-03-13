<?php
function getStream(array $data, $filename = 'php://output')
{
    return function () use ($data, $filename) {
        $output_stream = fopen($filename, 'w');

        // Put the rest of the items
        foreach ($data as $array) {
            fputcsv($output_stream, $array);
        }

        fclose($output_stream);
    };
}

function csv_headers($title)
{
    return [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
        ,   'Content-type'        => 'text/csv'
        ,   'Content-Disposition' => 'attachment; filename=' . str_slug($title) . '.csv'
        ,   'Expires'             => '0'
        ,   'Pragma'              => 'public',
    ];
}