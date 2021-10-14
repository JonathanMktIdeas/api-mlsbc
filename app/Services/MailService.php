<?php

namespace App\Services;

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;

class MailService
{
    private $transport;
    public function __construct()
    {
        $this->transport = (new Swift_SmtpTransport('mail.mlsbcs.com.mx', 465, 'ssl'))
            ->setUsername('webadmin@mlsbcs.com.mx')
            ->setPassword("ad@mls2021");
    }

    public function send($subject, $email, $emailName, string $template, array $data)
    {
        $mailer    = new Swift_Mailer($this->transport);
        $emailBody = view("emails/{$template}", $data)->render();

        $message = (new Swift_Message($subject))
            ->setFrom(['webadmin@mlsbcs.com.mx' => 'MLSBCS SA DE CV'])
            ->setTo([$email => $emailName])
            ->addPart($emailBody, 'text/html');
            // ->setBody('Here is the message itself');
            // ->attach(Swift_Attachment::fromPath('my-document.pdf'))
        $result = $mailer->send($message);

        return $result;
    }

    public function subscribe($file)
    {
        // $data = base64_decode($file);
        // $file = imagecreatefromstring($data);
        // $data = base64_decode($file);
        // $img = \Image::make($file->getRealPath());
        // $img->resize(120, 120, function ($constraint) {
        //     $constraint->aspectRatio();
        // });
        $extension = 'png';
        $name = date('YmdHis') . '_member.png';
        $fileName = $name;
        $fullPath = storage_path('app') . '/attachments/';
        // file_put_contents($fullPath.$fileName, $data);
        // move_uploaded_file($file['tmp_name'], $fullPath.$fileName);
        $file->move($fullPath, $fileName);
        // $contents = file_get_contents($file);
        // $base64   = base64_encode($contents);
        // file_put_contents($fullPath.$fileName, $base64);
        // $actual = file_get_contents($file);
        $mailer    = new Swift_Mailer($this->transport);
        $emailBody = view("emails/subscribe")->render();
        // $attachment = new Swift_Attachment($file, $name);

        $message = (new Swift_Message('Be a member'))
            ->setFrom(['webadmin@mlsbcs.com.mx' => 'MLSBCS SA DE CV'])
            ->setTo(['jonathangtz.sosa@gmail.com' => 'Jonathan.'])
            ->addPart($emailBody, 'text/html')
            ->attach(Swift_Attachment::fromPath($fullPath . $fileName));
            // ->setBody('Here is the message itself');
            // ->attach($attachment);
        $result = $mailer->send($message);

        return $result;
    }
}
