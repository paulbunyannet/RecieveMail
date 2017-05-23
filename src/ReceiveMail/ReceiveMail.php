<?php
namespace Pbc\ReceiveMail;

// Main ReceiveMail Class File - Version 1.1 (02-06-2009)
/*
 * File: ReceiveMail.php
 * Description: Receiving mail With Attachments
 * Version: 1.1
 * Created: 01-03-2006
 * Modified: 02-06-2009
 * Author: Mitul Koradia
 * Email: mitulkoradia@gmail.com
 * Cell : +91 9825273322
 */


/**
 * Class ReceiveMail
 * @package Pbc\ReceiveMail
 */
class ReceiveMail
{
    /**
     * @var string
     */
    protected $server = '';
    /**
     * @var string
     */
    protected $username = '';
    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var mixed $connectionType IMAP / POP connection
     */
    protected $connection = '';

    /**
     * @var string
     */
    protected $email = '';


    /**
     * @var string
     */
    protected $mailServer = 'localhost';

    /**
     * @var string
     */
    protected $serverType = 'pop';

    /**
     * @var string
     */
    protected $port = '110';

    /**
     * @var int
     */
    protected $imapPort = "443";

    /**
     * @var bool
     */
    protected $ssl = false;

    /**
     * @param array $params
     * @throws \Exception
     */
    public function __construct(array $params)
    {

            foreach ($params as $key => $value) {
                if(property_exists($this, 'set'.ucfirst($key))) {
                  $this->{'set' . ucfirst($key)}($value);
                } else {
                  throw new \Exception("Unknown field \"$key\"", 1);
                }
            }



        if ($this->getServertype() == 'imap') {
            // if port is not set and serverType is set to imap then default to port 443
            if ($this->getPort() == '') {
                $this->setPort($this->getImapPort());
            }
            $this->setServer('{' . $this->getMailserver() . ':' . $this->getPort() . '}INBOX');
        } else {
            $this->setServer('{' . $this->getMailServer() . ':' . $this->getPort() . '/pop3' . ($this->getSsl() ? "/ssl" : "") . '}INBOX');
        }

        return $this;

    }

    /**
     * @return mixed
     */
    public function getServerType()
    {
        return $this->serverType;
    }

    /**
     * @param mixed $servertype
     */
    public function setServerType($servertype)
    {
        $this->serverType = $servertype;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getMailServer()
    {
        return $this->mailServer;
    }

    /**
     * @param mixed $mailserver
     */
    public function setMailServer($mailserver)
    {
        $this->mailServer = $mailserver;
    }

    /**
     * @return mixed
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * @param mixed $ssl
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;
    }

    /**
     *
     */
    function connect() //Connect To the Mail Box
    {

        $this->setConnection(@imap_open($this->getServer(), $this->getUsername(), $this->getPassword()));

        if (!$this->getConnection()) {
            throw new \Exception("Error: Connecting to mail server");
        }
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $marubox
     */
    public function setConnection($marubox)
    {
        $this->connection = $marubox;
    }

    /**
     * @param $mid
     *
     * @return array|bool
     */
    function getHeaders($mid) // Get Header info
    {
        if (!$this->getConnection()) {
            return false;
        }

        $mailHeader = imap_header($this->getConnection(), $mid);
        $sender = $mailHeader->from[0];
        $senderReplyTo = $mailHeader->reply_to[0];
        $mailDetails = null;
        if (strtolower($sender->mailbox) != 'mailer-daemon' && strtolower($sender->mailbox) != 'postmaster') {
            $mailDetails = array(
                'from' => strtolower($sender->mailbox) . '@' . $sender->host,
                'fromName' => $sender->personal,
                'toOth' => strtolower($senderReplyTo->mailbox) . '@' . $senderReplyTo->host,
                'toNameOth' => $senderReplyTo->personal,
                'subject' => $mailHeader->subject,
                'to' => strtolower($mailHeader->toaddress)
            );
        }
        return $mailDetails;
    }

    /**
     * @return bool|int
     */
    function getTotalMails() //Get Total Number off Unread Email In Mailbox
    {
        if (!$this->getConnection()) {
            return false;
        }

        $headers = imap_headers($this->getConnection());
        return count($headers);
    }

    protected function decodeAttachment($message, $enc = 5)
    {
        if ($enc == 0) {
            $message = imap_8bit($message);
        }
        if ($enc == 1) {
            $message = imap_8bit($message);
        }
        if ($enc == 2) {
            $message = imap_binary($message);
        }
        if ($enc == 3) {
            $message = imap_base64($message);
        }
        if ($enc == 4) {
            $message = quoted_printable_decode($message);
        }
        /* if ($enc == 5)
            $message = $message;*/

        return $message;
    }

    /**
     * @param $mid
     * @param $path
     *
     * @return bool|string
     */
    function getAttach($mid, $path) // Get Atteced File from Mail
    {
        if (!$this->getConnection()) {
            return false;
        }

        $structure = imap_fetchstructure($this->getConnection(), $mid);
        $attachmentRequest = "";
        if ($structure->parts) {
            foreach (array_keys($structure->parts) as $key) {
                $enc = $structure->parts[$key]->encoding;
                if ($structure->parts[$key]->ifdparameters) {
                    $name = $structure->parts[$key]->dparameters[0]->value;

                    $message = $this->decodeAttachment(imap_fetchbody($this->getConnection(), $mid, $key + 1), $enc);

                    $fp = fopen($path . $name, "w");
                    fwrite($fp, $message);
                    fclose($fp);
                    $attachmentRequest = $attachmentRequest . $name . ",";
                }
                // Support for embedded attachments starts here
                if ($structure->parts[$key]->parts) {
                    foreach (array_keys($structure->parts[$key]->parts) as $keyb) {
                        $enc = $structure->parts[$key]->parts[$keyb]->encoding;
                        if ($structure->parts[$key]->parts[$keyb]->ifdparameters) {
                            $name = $structure->parts[$key]->parts[$keyb]->dparameters[0]->value;
                            $partnro = ($key + 1) . "." . ($keyb + 1);

                            $message = $this->decodeAttachment(imap_fetchbody($this->getConnection(), $mid, $partnro),
                                $enc);

                            $fp = fopen($path . $name, "w");
                            fwrite($fp, $message);
                            fclose($fp);
                            $attachmentRequest = $attachmentRequest . $name . ",";
                        }
                    }
                }
            }
        }
        $attachmentRequest = substr($attachmentRequest, 0, (strlen($attachmentRequest) - 1));
        return $attachmentRequest;
    }

    /**
     * @param $mid
     *
     * @return bool|string
     */
    function getBody($mid) // Get Message Body
    {
        if (!$this->getConnection()) {
            return false;
        }

        $body = $this->getPart($this->getConnection(), $mid, "TEXT/HTML");
        if ($body == "") {
            $body = $this->getPart($this->getConnection(), $mid, "TEXT/PLAIN");
        }
        if ($body == "") {
            return "";
        }
        return $body;
    }

    /**
     * @param      $stream
     * @param      $msgNumber
     * @param      $mimeType
     * @param bool $structure
     * @param bool $partNumber
     *
     * @return bool|string
     */
    function getPart(
        $stream,
        $msgNumber,
        $mimeType,
        $structure = false,
        $partNumber = false
    ) //Get Part Of Message Internal Private Use
    {
        if (!$structure) {
            $structure = imap_fetchstructure($stream, $msgNumber);
        }
        if ($structure) {
            if ($mimeType == $this->getMimeType($structure)) {
                if (!$partNumber) {
                    $partNumber = "1";
                }
                $text = imap_fetchbody($stream, $msgNumber, $partNumber);
                if ($structure->encoding == 3) {
                    return imap_base64($text);
                } else {
                    if ($structure->encoding == 4) {
                        return imap_qprint($text);
                    } else {
                        return $text;
                    }
                }
            }
            if ($structure->type == 1) /* multipart */ {
                while (list($index, $subStructure) = each($structure->parts)) {
                    $prefix = null;
                    if ($partNumber) {
                        $prefix = $partNumber . '.';
                    }
                    $data = $this->getPart($stream, $msgNumber, $mimeType, $subStructure, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $structure
     *
     * @return string
     */
    function getMimeType(&$structure) //Get Mime type Internal Private Use
    {
        $primaryMimeType = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");

        if ($structure->subtype) {
            return $primaryMimeType[(int)$structure->type] . '/' . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }

    /**
     * @param $mid
     *
     * @return bool
     */
    function deleteMails($mid) // Delete That Mail
    {
        if (!$this->getConnection()) {
            return false;
        }

        imap_delete($this->getConnection(), $mid);

        return $this;
    }

    /**
     * @return bool
     */
    function closeMailbox() //Close Mail Box
    {
        if (!$this->getConnection()) {
            return false;
        }

        imap_close($this->getConnection(), CL_EXPUNGE);

        return $this;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getImapPort()
    {
        return $this->imapPort;
    }

    /**
     * @param int $imapPort
     */
    public function setImapPort($imapPort)
    {
        $this->imapPort = $imapPort;
    }
}

?>
