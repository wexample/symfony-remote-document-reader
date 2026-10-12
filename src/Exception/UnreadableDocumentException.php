<?php

namespace Wexample\SymfonyRemoteDocumentReader\Exception;

/**
 * The service is up but the file cannot be read (not a PDF, a broken one): the
 * caller's to deal with, never worth a retry.
 */
class UnreadableDocumentException extends DocumentReaderException
{
}
