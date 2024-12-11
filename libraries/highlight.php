<?php

/* This software is licensed through a BSD-style License.
 * http://www.opensource.org/licenses/bsd-license.php

Copyright (c) 2003, 2004, Jacob D. Cohen
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
Neither the name of Jacob D. Cohen nor the names of his contributors
may be used to endorse or promote products derived from this software
without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

function keyword_replace($keywords, $text, $ncs = false)
{
    $cm = ($ncs) ? "i" : "";
    foreach ($keywords as $keyword) {
        $search[]  = "/(\\b$keyword\\b)/" . $cm;
        $replace[] = '<span class="keyword">\\0</span>';
    }

    $search[]  = "/(\\bclass\s)/";
    $replace[] = '<span class="keyword">\\0</span>';

    return preg_replace($search, $replace, $text);
}


function preproc_replace($preproc, $text)
{
    foreach ($preproc as $proc) {
        $search[] = "/(\\s*#\s*$proc\\b)/";
        $replace[] = '<span class="keyword">\\0</span>';
    }

    return preg_replace($search, $replace, $text);
}


function sch_syntax_helper($text)
{
    return $text;
}


function syntax_highlight_helper($text, $language)
{
    $preproc = array();
    $preproc["C++"] = array(
    "if",    "ifdef",   "ifndef", "elif",  "else",
    "endif", "include", "define", "undef", "line",
    "error", "pragma");
    $preproc["C89"] = & $preproc["C++"];
    $preproc["C"] = & $preproc["C89"];

    $keywords = array(
    "C++" => array(
    "asm",          "auto",      "bool",     "break",            "case",
    "catch",        "char",      /*class*/   "const",            "const_cast",
    "continue",     "default",   "delete",   "do",               "double",
    "dynamic_cast", "else",      "enum",     "explicit",         "export",
    "extern",       "false",     "float",    "for",              "friend",
    "goto",         "if",        "inline",   "int",              "long",
    "mutable",      "namespace", "new",      "operator",         "private",
    "protected",    "public",    "register", "reinterpret_cast", "return",
    "short",        "signed",    "sizeof",   "static",           "static_cast",
    "struct",       "switch",    "template", "this",             "throw",
    "true",         "try",       "typedef",  "typeid",           "typename",
    "union",        "unsigned",  "using",    "virtual",          "void",
    "volatile",     "wchar_t",   "while"),

    "C89" => array(
    "auto",     "break",    "case",     "char",     "const",
    "continue", "default",  "do",       "double",   "else",
    "enum",     "extern",   "float",    "for",      "goto",
    "if",       "int",      "long",     "register", "return",
    "short",    "signed",   "sizeof",   "static",   "struct",
    "switch",   "typedef",  "union",    "unsigned", "void",
    "volatile", "while"),

    "C" => array(
    "auto",     "break",    "case",     "char",     "const",
    "continue", "default",  "do",       "double",   "else",
    "enum",     "extern",   "float",    "for",      "goto",
    "if",       "int",      "long",     "register", "return",
    "short",    "signed",   "sizeof",   "static",   "struct",
    "switch",   "typedef",  "union",    "unsigned", "void",
    "volatile", "while",    "__restrict","_Bool"),

    "PHP" => array(
    "and",          "or",           "xor",      "__FILE__",     "__LINE__",
    "array",        "as",           "break",    "case",         "cfunction",
    /*class*/       "const",        "continue", "declare",      "default",
    "die",          "do",           "echo",     "else",         "elseif",
    "empty",        "enddeclare",   "endfor",   "endforeach",   "endif",
    "endswitch",    "endwhile",     "eval",     "exit",         "extends",
    "for",          "foreach",      "function", "global",       "if",
    "include",      "include_once", "isset",    "list",         "new",
    "old_function", "print",        "require",  "require_once", "return",
    "static",       "switch",       "unset",    "use",          "var",
    "while",        "__FUNCTION__", "__CLASS__"),

    "Perl" => array(
    "-A",           "-B",           "-C",       "-M",           "-O",
    "-R",           "-S",           "-T",       "-W",           "-X",
    "-b",           "-c",           "-d",       "-e",           "-f",
    "-g",           "-k",           "-l",       "-o",           "-p",
    "-r",           "-s",           "-t",       "-u",           "-w",
    "-x",           "-z",           "ARGV",     "DATA",         "ENV",
    "SIG",          "STDERR",       "STDIN",    "STDOUT",       "atan2",
    "bind",         "binmode",      "bless",    "caller",       "chdir",
    "chmod",        "chomp",        "chop",     "chown",        "chr",
    "chroot",       "close",        "closedir", "cmp",          "connect",
    "continue",     "cos",          "crypt",    "dbmclose",     "dbmopen",
    "defined",      "delete",       "die",      "do",           "dump",
    "each",         "else",         "elsif",    "endgrent",     "endhostent",
    "endnetent",    "endprotent",   "endpwent", "endservent",   "eof",
    "eq",           "eval",         "exec",     "exists",       "exit",
    "exp",          "fcntl",        "fileno",   "flock",        "for",
    "foreach",      "fork",         "format",   "formline",     "ge",
    "getc",         "getgrent",     "getgrid",  "getgrnam",     "gethostbyaddr",
    "gethostbyname","gethostent",   "getlogin", "getnetbyaddr", "getnetbyname",
    "getnetent",    "getpeername",  "getpgrp",  "getppid",      "getpriority",
    "getprotobyname","getprotobynumber","getprotoent","getpwent","getpwnam",
    "getpwuid",     "getservbyname","getservbyport","getservent","getsockname",
    "getsockopt",   "glob",         "gmtime",   "goto",         "grep",
    /*gt*/          "hex",          "if",       "import",       "index",
    "int",          "ioctl",        "join",     "keys",         "kill",
    "last",         "lc",           "lcfirst",  "le",           "length",
    "link",         "listen",       "local",    "localtime",    "log",
    "lstat",        /*lt*/          "m",        "map",          "mkdir",
    "msgctl",       "msgget",       "msgrcv",   "msgsnd",       "my",
    "ne",           "next",         "no",       "oct",          "open",
    "opendir",      "ord",          "pack",     "package",      "pipe",
    "pop",          "pos",          "print",    "printf",       "push",
    "q",            "qq",           "quotemeta","qw",           "qx",
    "rand",         "read",         "readdir",  "readlink",     "recv",
    "redo",         "ref",          "refname",  "require",      "reset",
    "return",       "reverse",      "rewinddir","rindex",       "rmdir",
    "s",            "scalar",       "seek",     "seekdir",      "select",
    "semctl",       "semget",       "semop",    "send",         "setgrent",
    "sethostent",   "setnetent",    "setpgrp",  "setpriority",  "setprotoent",
    "setpwent",     "setservent",   "setsockopt","shift",       "shmctl",
    "shmget",       "shmread",      "shmwrite", "shutdown",     "sin",
    "sleep",        "socket",       "socketpair","sort",        "splice",
    "split",        "sprintf",      "sqrt",     "srand",        "stat",
    "study",        "sub",          "substr",   "symlink",      "syscall",
    "sysopen",      "sysread",      "system",   "syswrite",     "tell",
    "telldir",      "tie",          "tied",     "time",         "times",
    "tr",           "truncate",     "uc",       "ucfirst",      "umask",
    "undef",        "unless",       "unlink",   "unpack",       "unshift",
    "untie",        "until",        "use",      "utime",        "values",
    "vec",          "wait",         "waitpid",  "wantarray",    "warn",
    "while",        "write",        "y",        "or",           "and",
    "not"),

    "Java" => array(
    "abstract",     "boolean",      "break",    "byte",         "case",
    "catch",        "char",         /*class*/   "const",        "continue",
    "default",      "do",           "double",   "else",         "extends",
    "final",        "finally",      "float",    "for",          "goto",
    "if",           "implements",   "import",   "instanceof",   "int",
    "interface",    "long",         "native",   "new",          "package",
    "private",      "protected",    "public",   "return",       "short",
    "static",       "strictfp",     "super",    "switch",       "synchronized",
    "this",         "throw",        "throws",   "transient",    "try",
    "void",         "volatile",     "while"),

    "VB" => array(
    "AddressOf",    "Alias",        "And",      "Any",          "As",
    "Binary",       "Boolean",      "ByRef",    "Byte",         "ByVal",
    "Call",         "Case",         "CBool",    "CByte",        "CCur",
    "CDate",        "CDbl",         "CInt",     "CLng",         "Close",
    "Const",        "CSng",         "CStr",     "Currency",     "CVar",
    "CVErr",        "Date",         "Debug",    "Declare",      "DefBool",
    "DefByte",      "DefCur",       "DefDate",  "DefDbl",       "DefInt",
    "DefLng",       "DefObj",       "DefSng",   "DefStr",       "DefVar",
    "Dim",          "Do",           "Double",   "Each",         "Else",
    "End",          "Enum",         "Eqv",      "Erase",        "Error",
    "Event",        "Exit",         "For",      "Friend",       "Function",
    "Get",          "Get",          "Global",   "GoSub",        "GoTo",
    "If",           "Imp",          "Implements","In",          "Input",
    "Integer",      "Is",           "LBound",   "Len",          "Let",
    "Lib",          "Like",         "Line",     "Lock",         "Long",
    "Loop",         "LSet",         "Mod",      "Name",         "Next",
    "Not",          "Nothing",      "Null",     "Object",       "On",
    "Open",         "Option Base 1","Option Compare Binary",
    "Option Compare Database", "Option Compare Text", "Option Explicit",
    "Option Private Module", "Optional",        "Or",           "Output",
    "ParamArray",   "Preserve",     "Print",    "Private",      "Property",
    "Public",       "Put",          "RaiseEvent","Random",      "Read",
    "ReDim",        "Resume",       "Return",   "RSet",         "Seek",
    "Select",       "Set",          "Single",   "Spc",          "Static",
    "Step",         "Stop",         "String",   "Sub",          "Tab",
    "Then",         "To",           "Type",     "UBound",       "Unlock",
    "Variant",      "Wend",         "While",    "With",         "WithEvents",
    "Write",        "Xor"),

    "C#" => array(
    "abstract",     "as",           "base",     "bool",         "break",
    "byte",         "case",         "catch",    "char",         "checked",
    /*class*/       "const",        "continue", "decimal",      "default",
    "delegate",     "do",           "double",   "else",         "enum",
    "event",        "explicit",     "extern",   "false",        "finally",
    "fixed",        "float",        "for",      "foreach",      "goto",
    "if",           "implicit",     "in",       "int",          "interface",
    "internal",     "is",           "lock",     "long",         "namespace",
    "new",          "null",         "object",   "operator",     "out",
    "override",     "params",       "private",  "protected",    "public",
    "readonly",     "ref",          "return",   "sbyte",        "sealed",
    "short",        "sizeof",       "stackalloc","static",      "string",
    "struct",       "switch",       "this",     "throw",        "true",
    "try",          "typeof",       "uint",     "ulong",        "unchecked",
    "unsafe",       "ushort",       "using",    "virtual",      "volatile",
    "void",         "while"),

    "Ruby" => array(
    "alias",        "and",          "begin",    "break",        "case",
    /*class*/       "def",          "defined",  "do",           "else",
    "elsif",        "end",          "ensure",   "false",        "for",
    "if",           "in",           "module",   "next",         "module",
    "next",         "nil",          "not",      "or",           "redo",
    "rescue",       "retry",        "return",   "self",         "super",
    "then",         "true",         "undef",    "unless",       "until",
    "when",         "while",        "yield"),

    "Python" => array(
    "and",          "assert",       "break",    /*"class",*/    "continue",
    "def",          "del",          "elif",     "else",         "except",
    "exec",         "finally",      "for",      "from",         "global",
    "if",           "import",       "in",       "is",           "lambda",
    "not",          "or",           "pass",     "print",        "raise",
    "return",       "try",          "while",    "yield"),

    "Pascal" => array(
    "Absolute",     "Abstract",     "All",      "And",          "And_then",
    "Array",        "Asm",          "Begin",    "Bindable",     "Case",
    /*"Class",*/    "Const",        "Constructor","Destructor", "Div",
    "Do",           "Downto",       "Else",     "End",          "Export",
    "File",         "For",          "Function", "Goto",         "If",
    "Import",       "Implementation","Inherited","In",          "Inline",
    "Interface",    "Is",           "Label",    "Mod",          "Module",
    "Nil",          "Not",          "Object",   "Of",           "Only",
    "Operator",     "Or",           "Or_else",  "Otherwise",    "Packed",
    "Pow",          "Procedure",    "Program",  "Property",     "Protected",
    "Qualified",    "Record",       "Repeat",   "Restricted",   "Set",
    "Shl",          "Shr",          "Then",     "To",           "Type",
    "Unit",         "Until",        "Uses",     "Value",        "Var",
    "View",         "Virtual",      "While",    "With",         "Xor"),

    "mIRC" => array(
        ),

    "PL/I" => array(
    "A",            "ABS",            "ACOS",        "%ACTIVATE",    "ACTUALCOUNT",
    "ADD",            "ADDR",            "ADDREL",    "ALIGNED",        "ALLOCATE",
    "ALLOC",        "ALLOCATION",    "ALLOCN",    "ANY",            "ANYCONDITION",
    "APPEND",        "AREA",            "ASIN",        "ATAN",            "ATAND",
    "ATANH",        "AUTOMATIC",    "AUTO",        "B",            "B1",
    "B2",            "B3",            "B4",        "BACKUP_DATE",    "BASED",
    "BATCH",        "BEGIN",        "BINARY",    "BIN",            "BIT",
    "BLOCK_BOUNDARY_FORMAT",        "BLOCK_IO",    "BLOCK_SIZE",    "BOOL",
    "BUCKET_SIZE",    "BUILTIN",        "BY",        "BYTE",            "BYTESIZE",
    "CALL",            "CANCEL_CONTROL_O",            "CARRIAGE_RETURN_FORMAT",
    "CEIL",            "CHAR", "CHARACTER",    "CLOSE",    "COLLATE",        "COLUMN",
    "CONDITION",    "CONTIGUOUS",    "CONTIGUOUS_BEST_TRY",        "CONTROLLED",
    "CONVERSION",    "COPY",            "COS",        "COSD",            "COSH",
    "CREATION_DATE",                "CURRENT_POSITION",            "DATE",
    "DATETIME",        "%DEACTIVATE",    "DECIMAL",    "DEC",            "%DECLARE",
    "%DCL",            "DECLARE",        "DCL",        "DECODE",        "DEFAULT_FILE_NAME",
    "DEFERRED_WRITE",                "DEFINED",    "DEF",            "DELETE",
    "DESCRIPTOR",    "%DICTIONARY",    "DIMENSION","DIM",           "DIRECT",
    "DISPLAY",        "DIVIDE",        "%DO",        "DO",            "E",
    "EDIT",            "%ELSE",        "ELSE",        "EMPTY",        "ENCODE",
    "%END",            "END",            "ENDFILE",    "ENDPAGE",        "ENTRY",
    "ENVIRONMENT",    "ENV",            "%ERROR",    "ERROR",        "EVERY",
    "EXP",            "EXPIRATION_DATE",            "EXTEND",        "EXTENSION_SIZE",
    "EXTERNAL",        "EXT",            "F",        "FAST_DELETE",    "%FATAL",
    "FILE",            "FILE_ID",        "FILE_ID_TO",                "FILE_SIZE",
    "FINISH",        "FIXED",        "FIXEDOVERFLOW",            "FOFL",
    "FIXED_CONTROL_FROM",            "FIXED_CONTROL_SIZE",        "FIXED_CONTROL_SIZE_TO",
    "FIXED_CONTROL_TO",                "FIXED_LENGTH_RECORDS",        "FLOAT",
    "FLOOR",        "FLUSH",        "FORMAT",    "FREE",            "FROM",
    "GET",            "GLOBALDEF",    "GLOBALREF",                "%GOTO",
    "GOTO",            "GO", "TO",        "GROUP_PROTETION",            "HBOUND",
    "HIGH",            "INDENT",        "%IF",        "IF",            "IGNORE_LINE_MARKS",
    "IN",            "%INCLUDE",        "INDEX",    "INDEXED",        "INDEX_NUMBER",
    "%INFORM",        "INFORM",        "INITIAL",    "INIT",            "INITIAL_FILL",
    "INPUT",        "INT",            "INTERNAL",    "INTO",            "KEY",
    "KEYED",        "KEYFROM",        "KEYTO",    "LABEL",        "LBOUND",
    "LEAVE",        "LENGTH",        "LIKE",        "LINE",            "LINENO",
    "LINESIZE",        "%LIST",        "LIST",        "LOCK_ON_READ",    "LOCK_ON_WRITE",
    "LOG",            "LOG10",        "LOG2",        "LOW",            "LTRIM",
    "MAIN",            "MANUAL_UNLOCKING",            "MATCH_GREATER",
    "MATCH_GREATER_EQUAL",            "MATCH_NEXT",                "MATCH_NEXT_EQUAL",
    "MAX",            "MAXIMUM_RECORD_NUMBER",    "MAXIMUM_RECORD_SIZE",
    "MAXLENGTH",    "MEMBER",        "MIN",        "MOD",            "MULTIBLOCK_COUNT",
    "MULTIBUFFER_COUNT",            "MULTIPLY",    "NEXT_VOLUME",    "%NOLIST",
    "NOLOCK",        "NONEXISTENT_RECORD",        "NONRECURSIVE",    "NONVARYING",
    "NONVAR",        "NORESCAN",        "NO_ECHO",    "NO_FILTER",    "NO_SHARE",
    "NULL",            "OFFSET",        "ON",        "ONARGSLIST",    "ONCHAR",
    "ONCODE",        "ONFILE",        "ONKEY",    "ONSOURCE",        "OPEN",
    "OPTIONAL",        "OPTIONS",        "OTHERWISE","OTHER",        "OUTPUT",
    "OVERFLOW",        "OFL",            "OWNER_GROUP",                "OWNER_ID",
    "OWNER_MEMBER",    "OWNER_PROTECTION",            "P",            "%PAGE",
    "PAGE",            "PAGENO",        "PAGESIZE",    "PARAMETER",    "PARM",
    "PICTURE",        "PIC",            "POINTER",    "PTR",            "POSINT",
    "POSITION",        "POS",            "PRECISION","PREC",            "PRESENT",
    "PRINT",        "PRINTER_FORMAT",            "%PROCEDURE",    "%PROC",
    "PROCEDURE",    "PROC",            "PROD",        "PROMPT",        "PURGE_TYPE_AHEAD",
    "PUT",            "R",            "RANK",        "READ",            "READONLY",
    "READ_AHEAD",    "READ_CHECK",    "READ_REGARDLESS",            "RECORD",
    "RECORD_ID",    "RECORD_ID_ACCESS",            "RECORD_ID_TO",    "RECURSIVE",
    "REFER",        "REFERENCE",    "RELEASE",    "REPEAT",        "%REPLACE",
    "RESCAN",        "RESIGNAL",        "RETRIEVAL_POINTERS",        "%RETURN",
    "RETURN",        "RETURNS",        "REVERSE",    "REVERT",        "REVISION_DATE",
    "REWIND",        "REWIND_ON_CLOSE",            "REWIND_ON_OPEN",
    "REWRITE",        "ROUND",        "RTRIM",    "%SBTTL",        "SCALARVARYING",
    "SEARCH",        "SELECT",        "SEQUENTIAL",                "SEQL",
    "SET",            "SHARED_READ",    "SHARED_WRITE",                "SIGN",
    "SIGNAL",        "SIN",            "SIND",        "SINH",            "SIZE",
    "SKIP",            "SNAP",            "SOME",        "SPACEBLOCK",    "SPOOL",
    "SQRT",            "STATEMENT",    "STATIC",    "STOP",            "STORAGE",
    "STREAM",        "STRING",        "STRINGRANGE",                "STRG",
    "STRUCTURE",    "SUBSCRIPTRANGE",            "SUBRG",        "SUBSTR",
    "SUBTRACT",        "SUM",            "SYSIN",        "SYSPRINT",
    "SYSTEM",        "SYSTEM_PROTECTION",        "TAB",            "TAN",
    "TAND",            "TANH",            "TEMPORARY","%THEN",        "THEN",
    "TIME",            "TIMEOUT_PERIOD",            "%TITLE",        "TITLE",
    "TO",            "TRANSLATE",    "TRIM",        "TRUNC",        "TRUNCATE",
    "UNALIGNED",    "UNAL",            "UNDEFINED","UNDF",            "UNDERFLOW",
    "UFL",            "UNION",        "UNSPEC",    "UNTIL",        "UPDATE",
    "USER_OPEN",    "VALID",        "VALUE",    "VAL",            "VARIABLE",
    "VARIANT",        "VARYING",        "VAR",        "VAXCONDITION",    "VERIFY",
    "WAIT_FOR_RECORD",                "%WARN",    "WARN",            "WHEN",
    "WHILE",        "WORLD_PROTECTION",            "WRITE",        "WRITE_BEHIND",
    "WRITE_CHECK",    "X",            "ZERODIVIDE"),

    "SQL" => array(
    "abort", "abs", "absolute", "access",
    "action", "ada", "add", "admin",
    "after", "aggregate", "alias", "all",
    "allocate", "alter", "analyse", "analyze",
    "and", "any", "are", "array",
    "as", "asc", "asensitive", "assertion",
    "assignment", "asymmetric", "at", "atomic",
    "authorization", "avg", "backward", "before",
    "begin", "between", "bigint", "binary",
    "bit", "bitvar", "bit_length", "blob",
    "boolean", "both", "breadth", "by",
    "c", "cache", "call", "called",
    "cardinality", "cascade", "cascaded", "case",
    "cast", "catalog", "catalog_name", "chain",
    "char", "character", "characteristics", "character_length",
    "character_set_catalog", "character_set_name", "character_set_schema", "char_length",
    "check", "checked", "checkpoint", /* "class", */
    "class_origin", "clob", "close", "cluster",
    "coalesce", "cobol", "collate", "collation",
    "collation_catalog", "collation_name", "collation_schema", "column",
    "column_name", "command_function", "command_function_code", "comment",
    "commit", "committed", "completion", "condition_number",
    "connect", "connection", "connection_name", "constraint",
    "constraints", "constraint_catalog", "constraint_name", "constraint_schema",
    "constructor", "contains", "continue", "conversion",
    "convert", "copy", "corresponding", "count",
    "create", "createdb", "createuser", "cross",
    "cube", "current", "current_date", "current_path",
    "current_role", "current_time", "current_timestamp", "current_user",
    "cursor", "cursor_name", "cycle", "data",
    "database", "date", "datetime_interval_code", "datetime_interval_precision",
    "day", "deallocate", "dec", "decimal",
    "declare", "default", "defaults", "deferrable",
    "deferred", "defined", "definer", "delete",
    "delimiter", "delimiters", "depth", "deref",
    "desc", "describe", "descriptor", "destroy",
    "destructor", "deterministic", "diagnostics", "dictionary",
    "disconnect", "dispatch", "distinct", "do",
    "domain", "double", "drop", "dynamic",
    "dynamic_function", "dynamic_function_code", "each", "else",
    "encoding", "encrypted", "end", "end-exec",
    "equals", "escape", "every", "except",
    "exception", "excluding", "exclusive", "exec",
    "execute", "existing", "exists", "explain",
    "external", "extract", "false", "fetch",
    "final", "first", "float", "for",
    "force", "foreign", "fortran", "forward",
    "found", "free", "freeze", "from",
    "full", "function", "g", "general",
    "generated", "get", "global", "go",
    "goto", "grant", "granted", "group",
    "grouping", "handler", "having", "hierarchy",
    "hold", "host", "hour", "identity", "if",
    "ignore", "ilike", "immediate", "immutable",
    "implementation", "implicit", "in", "including",
    "increment", "index", "indicator", "infix",
    "inherits", "initialize", "initially", "inner",
    "inout", "input", "insensitive", "insert",
    "instance", "instantiable", "instead", "int",
    "integer", "intersect", "interval", "into",
    "invoker", "is", "isnull", "isolation",
    "iterate", "join", "k", "key",
    "key_member", "key_type", "lancompiler", "language",
    "large", "last", "lateral", "leading",
    "left", "length", "less", "level",
    "like", "limit", "listen", "load",
    "local", "localtime", "localtimestamp", "location",
    "locator", "lock", "loop", "lower", "m",
    "map", "match", "max", "maxvalue",
    "message_length", "message_octet_length", "message_text", "method",
    "min", "minute", "minvalue", "mod",
    "mode", "modifies", "modify", "module",
    "month", "more", "move", "mumps",
    "name", "names", "national", "natural",
    "nchar", "nclob", "new", "next",
    "no", "nocreatedb", "nocreateuser", "none",
    "not", "nothing", "notice", "notify", "notnull",
    "null", "nullable", "nullif", "number",
    "numeric", "object", "octet_length", "of",
    "off", "offset", "oids", "old",
    "on", "only", "open", "operation",
    "operator", "option", "options", "or",
    "order", "ordinality", "out", "outer",
    "output", "overlaps", "overlay", "overriding",
    "owner", "pad", "parameter", "parameters",
    "parameter_mode", "parameter_name", "parameter_ordinal_position", "parameter_specific_catalog",
    "parameter_specific_name", "parameter_specific_schema", "partial", "pascal",
    "password", "path", "pendant", "placing",
    "pli", "position", "postfix", "precision",
    "prefix", "preorder", "prepare", "preserve",
    "primary", "prior", "privileges", "procedural",
    "procedure", "public", "quote_ident", "quote_literal", "raise", "read", "reads",
    "real", "recheck", "record", "recursive", "ref", "refcursor",
    "references", "referencing", "reindex", "relative",
    "rename", "repeatable", "replace", "reset",
    "restart", "restrict", /* "result", */ "return",
    "returned_length", "returned_octet_length", "returned_sqlstate", "returns",
    "revoke", "right", "role", "rollback",
    "rollup", "routine", "routine_catalog", "routine_name",
    "routine_schema", "row", "rows", "row_count", "rowtype",
    "rule", "savepoint", "scale", "schema",
    "schema_name", "scope", "scroll", "search",
    "second", "section", "security", "select",
    "self", "sensitive", "sequence", "serializable",
    "server_name", "session", "session_user", "set",
    "setof", "sets", "share", "show",
    "similar", "simple", "size", "smallint",
    "some", "source", "space", "specific",
    "specifictype", "specific_name", "sql", "sqlcode",
    "sqlerror", "sqlexception", "sqlstate", "sqlwarning",
    "stable", "start", "state", "statement",
    "static", "statistics", "stdin", "stdout",
    "storage", "strict", "structure", "style",
    "subclass_origin", "sublist", "substring", "sum",
    "symmetric", "sysid", "system", "system_user",
    "table", "table_name", "temp", "template",
    "temporary", "terminate", "text", "than", "then",
    "time", "timestamp", "timezone_hour", "timezone_minute",
    "to", "toast", "trailing", "transaction",
    "transactions_committed", "transactions_rolled_back", "transaction_active", "transform",
    "transforms", "translate", "translation", "treat",
    "trigger", "trigger_catalog", "trigger_name", "trigger_schema",
    "trim", "true", "truncate", "trusted",
    "type", "uncommitted", "under", "unencrypted",
    "union", "unique", "unknown", "unlisten",
    "unnamed", "unnest", "until", "update",
    "upper", "usage", "user", "user_defined_type_catalog",
    "user_defined_type_name", "user_defined_type_schema", "using", "vacuum",
    "valid", "validator", "value", "values",
    "varchar", "variable", "varying", "verbose",
    "version", "view", "volatile", "when",
    "whenever", "where", "with", "without",
    "work", "write", "year", "zone")

    );

    $case_insensitive = array(
        "VB" => true,
        "Pascal" => true,
        "PL/I"   => true,
        "SQL"    => true
    );
    $ncs = false;
    if (array_key_exists($language, $case_insensitive)) {
        $ncs = true;
    }

    $text = (array_key_exists($language, $preproc)) ?
        preproc_replace($preproc[$language], $text) :
        $text;
    $text = (array_key_exists($language, $keywords)) ?
        keyword_replace($keywords[$language], $text, $ncs) :
        $text;

    return $text;
}


function rtrim1($span, $lang, $ch)
{
    return syntax_highlight_helper(substr($span, 0, -1), $lang);
}


function rtrim1_htmlesc($span, $lang, $ch)
{
    return htmlspecialchars(substr($span, 0, -1));
}


function sch_rtrim1($span, $lang, $ch)
{
    return sch_syntax_helper(substr($span, 0, -1));
}


function rtrim2($span, $lang, $ch)
{
    return substr($span, 0, -2);
}


function syn_proc($span, $lang, $ch)
{
    return syntax_highlight_helper($span, $lang);
}

function dash_putback($span, $lang, $ch)
{
    return syntax_highlight_helper('-' . $span, $lang);
}

function slash_putback($span, $lang, $ch)
{
    return syntax_highlight_helper('/' . $span, $lang);
}

function slash_putback_rtrim1($span, $lang, $ch)
{
    return rtrim1('/' . $span, $lang, $ch);
}

function lparen_putback($span, $lang, $ch)
{
    return syntax_highlight_helper('(' . $span, $lang);
}

function lparen_putback_rtrim1($span, $lang, $ch)
{
    return rtrim1('(' . $span, $lang, $ch);
}

function prepend_xml_opentag($span, $lang, $ch)
{
    return '<span class="XML_TAG">&lt;' . $span;
}

function proc_void($span, $lang, $ch)
{
    return $span;
}


/**
 * Syntax highlight function
 * Does the bulk of the syntax highlighting by lexing the input
 * string, then calling the helper function to highlight keywords.
 */
function syntax_highlight($text, $language)
{
    if ($language == "Plain Text") {
        return $text;
    }

    define("NORMAL_TEXT", 1, true);
    define("DQ_LITERAL", 2, true);
    define("DQ_ESCAPE", 3, true);
    define("SQ_LITERAL", 4, true);
    define("SQ_ESCAPE", 5, true);
    define("SLASH_BEGIN", 6, true);
    define("STAR_COMMENT", 7, true);
    define("STAR_END", 8, true);
    define("LINE_COMMENT", 9, true);
    define("HTML_ENTITY", 10, true);
    define("LC_ESCAPE", 11, true);
    define("BLOCK_COMMENT", 12, true);
    define("PAREN_BEGIN", 13, true);
    define("DASH_BEGIN", 14, true);
    define("BT_LITERAL", 15, true);
    define("BT_ESCAPE", 16, true);
    define("XML_TAG_BEGIN", 17, true);
    define("XML_TAG", 18, true);
    define("XML_PI", 19, true);
    define("SCH_NORMAL", 20, true);
    define("SCH_STRESC", 21, true);
    define("SCH_IDEXPR", 22, true);
    define("SCH_NUMLIT", 23, true);
    define("SCH_CHRLIT", 24, true);
    define("SCH_STRLIT", 25, true);

    $initial_state["Scheme"] = SCH_NORMAL;

    $sch[SCH_NORMAL][0]     = SCH_NORMAL;
    $sch[SCH_NORMAL]['"']   = SCH_STRLIT;
    $sch[SCH_NORMAL]["#"]   = SCH_CHRLIT;
    $sch[SCH_NORMAL]["0"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["1"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["2"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["3"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["4"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["5"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["6"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["7"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["8"]   = SCH_NUMLIT;
    $sch[SCH_NORMAL]["9"]   = SCH_NUMLIT;

    $sch[SCH_STRLIT]['"']   = SCH_NORMAL;
    $sch[SCH_STRLIT]["\n"]  = SCH_NORMAL;
    $sch[SCH_STRLIT]["\\"]  = SCH_STRESC;
    $sch[SCH_STRLIT][0]     = SCH_STRLIT;

    $sch[SCH_CHRLIT][" "]   = SCH_NORMAL;
    $sch[SCH_CHRLIT]["\t"]  = SCH_NORMAL;
    $sch[SCH_CHRLIT]["\n"]  = SCH_NORMAL;
    $sch[SCH_CHRLIT]["\r"]  = SCH_NORMAL;
    $sch[SCH_CHRLIT][0]     = SCH_CHRLIT;

    $sch[SCH_NUMLIT][" "]   = SCH_NORMAL;
    $sch[SCH_NUMLIT]["\t"]  = SCH_NORMAL;
    $sch[SCH_NUMLIT]["\n"]  = SCH_NORMAL;
    $sch[SCH_NUMLIT]["\r"]  = SCH_NORMAL;
    $sch[SCH_NUMLIT][0]     = SCH_NUMLIT;

    //
    // State transitions for C
    //
    $c89[NORMAL_TEXT]["\""] = DQ_LITERAL;
    $c89[NORMAL_TEXT]["'"]  = SQ_LITERAL;
    $c89[NORMAL_TEXT]["/"]  = SLASH_BEGIN;
    $c89[NORMAL_TEXT][0]    = NORMAL_TEXT;

    $c89[DQ_LITERAL]["\""]  = NORMAL_TEXT;
    $c89[DQ_LITERAL]["\n"]  = NORMAL_TEXT;
    $c89[DQ_LITERAL]["\\"]  = DQ_ESCAPE;
    $c89[DQ_LITERAL][0]     = DQ_LITERAL;

    $c89[DQ_ESCAPE][0]      = DQ_LITERAL;

    $c89[SQ_LITERAL]["'"]   = NORMAL_TEXT;
    $c89[SQ_LITERAL]["\n"]  = NORMAL_TEXT;
    $c89[SQ_LITERAL]["\\"]  = SQ_ESCAPE;
    $c89[SQ_LITERAL][0]     = SQ_LITERAL;

    $c89[SQ_ESCAPE][0]      = SQ_LITERAL;

    $c89[SLASH_BEGIN]["*"]  = STAR_COMMENT;
    $c89[SLASH_BEGIN][0]    = NORMAL_TEXT;

    $c89[STAR_COMMENT]["*"] = STAR_END;
    $c89[STAR_COMMENT][0]   = STAR_COMMENT;

    $c89[STAR_END]["/"]     = NORMAL_TEXT;
    $c89[STAR_END]["*"]     = STAR_END;
    $c89[STAR_END][0]       = STAR_COMMENT;

    //
    // State transitions for C++
    // Inherit transitions from C, and add line comment support
    //
    $cpp = $c89;
    $cpp[SLASH_BEGIN]["/"]   = LINE_COMMENT;
    $cpp[LINE_COMMENT]["\n"] = NORMAL_TEXT;
    $cpp[LINE_COMMENT]["\\"] = LC_ESCAPE;
    $cpp[LINE_COMMENT][0]    = LINE_COMMENT;

    $cpp[LC_ESCAPE]["\r"]    = LC_ESCAPE;
    $cpp[LC_ESCAPE][0]       = LINE_COMMENT;

    //
    // State transitions for C99.
    // C99 supports line comments like C++
    //
    $c99 = $cpp;

    // State transitions for PL/I
    // Kinda like C
    $pli = $c89;

    //
    // State transitions for PHP
    // Inherit transitions from C++, and add perl-style line comment support
    $php = $cpp;
    $php[NORMAL_TEXT]["#"]   = LINE_COMMENT;
    $php[SQ_LITERAL]["\n"]   = SQ_LITERAL;
    $php[DQ_LITERAL]["\n"]   = DQ_LITERAL;

    //
    // State transitions for Perl
    $perl[NORMAL_TEXT]["#"]  = LINE_COMMENT;
    $perl[NORMAL_TEXT]["\""] = DQ_LITERAL;
    $perl[NORMAL_TEXT]["'"]  = SQ_LITERAL;
    $perl[NORMAL_TEXT][0]    = NORMAL_TEXT;

    $perl[DQ_LITERAL]["\""]  = NORMAL_TEXT;
    $perl[DQ_LITERAL]["\\"]  = DQ_ESCAPE;
    $perl[DQ_LITERAL][0]     = DQ_LITERAL;

    $perl[DQ_ESCAPE][0]      = DQ_LITERAL;

    $perl[SQ_LITERAL]["'"]   = NORMAL_TEXT;
    $perl[SQ_LITERAL]["\\"]  = SQ_ESCAPE;
    $perl[SQ_LITERAL][0]     = SQ_LITERAL;

    $perl[SQ_ESCAPE][0]      = SQ_LITERAL;

    $perl[LINE_COMMENT]["\n"] = NORMAL_TEXT;
    $perl[LINE_COMMENT][0]    = LINE_COMMENT;

    $mirc[NORMAL_TEXT]["\""] = DQ_LITERAL;
    $mirc[NORMAL_TEXT][";"]  = LINE_COMMENT;
    $mirc[NORMAL_TEXT][0]    = NORMAL_TEXT;

    $mirc[DQ_LITERAL]["\""]  = NORMAL_TEXT;
    $mirc[DQ_LITERAL]["\\"]  = DQ_ESCAPE;
    $mirc[DQ_LITERAL][0]     = DQ_LITERAL;

    $mirc[DQ_ESCAPE][0]      = DQ_LITERAL;

    $mirc[LINE_COMMENT]["\n"] = NORMAL_TEXT;
    $mirc[LINE_COMMENT][0]   = LINE_COMMENT;

    $ruby = $perl;

    $python = $perl;

    $java = $cpp;

    $vb = $perl;
    $vb[NORMAL_TEXT]["#"] = NORMAL_TEXT;
    $vb[NORMAL_TEXT]["'"] = LINE_COMMENT;

    $cs = $java;

    $pascal = $c89;
    $pascal[NORMAL_TEXT]["("]  = PAREN_BEGIN;
    $pascal[NORMAL_TEXT]["/"]  = SLASH_BEGIN;
    $pascal[NORMAL_TEXT]["{"]  = BLOCK_COMMENT;

    $pascal[PAREN_BEGIN]["*"]  = STAR_COMMENT;
    $pascal[PAREN_BEGIN]["'"]  = SQ_LITERAL;
    $pascal[PAREN_BEGIN]['"']  = DQ_LITERAL;
    $pascal[PAREN_BEGIN][0]    = NORMAL_TEXT;

    $pascal[SLASH_BEGIN]["'"]  = SQ_LITERAL;
    $pascal[SLASH_BEGIN]['"']  = DQ_LITERAL;
    $pascal[SLASH_BEGIN]['/']  = LINE_COMMENT;
    $pascal[SLASH_BEGIN][0]    = NORMAL_TEXT;

    $pascal[STAR_COMMENT]["*"] = STAR_END;
    $pascal[STAR_COMMENT][0]   = STAR_COMMENT;

    $pascal[BLOCK_COMMENT]["}"] = NORMAL_TEXT;
    $pascal[BLOCK_COMMENT][0]   = BLOCK_COMMENT;

    $pascal[LINE_COMMENT]["\n"] = NORMAL_TEXT;
    $pascal[LINE_COMMENT][0]    = LINE_COMMENT;

    $pascal[STAR_END][")"]     = NORMAL_TEXT;
    $pascal[STAR_END]["*"]     = STAR_END;
    $pascal[STAR_END][0]       = STAR_COMMENT;

    $sql[NORMAL_TEXT]['"']     = DQ_LITERAL;
    $sql[NORMAL_TEXT]["'"]     = SQ_LITERAL;
    $sql[NORMAL_TEXT]['`']     = BT_LITERAL;
    $sql[NORMAL_TEXT]['-']     = DASH_BEGIN;
    $sql[NORMAL_TEXT][0]       = NORMAL_TEXT;

    $sql[DQ_LITERAL]['"']      = NORMAL_TEXT;
    $sql[DQ_LITERAL]['\\']     = DQ_ESCAPE;
    $sql[DQ_LITERAL][0]        = DQ_LITERAL;

    $sql[SQ_LITERAL]["'"]      = NORMAL_TEXT;
    $sql[SQ_LITERAL]['\\']     = SQ_ESCAPE;
    $sql[SQ_LITERAL][0]        = SQ_LITERAL;

    $sql[BT_LITERAL]['`']      = NORMAL_TEXT;
    $sql[BT_LITERAL]['\\']     = BT_ESCAPE;
    $sql[BT_LITERAL][0]        = BT_LITERAL;

    $sql[DQ_ESCAPE][0]         = DQ_LITERAL;
    $sql[SQ_ESCAPE][0]         = SQ_LITERAL;
    $sql[BT_ESCAPE][0]         = BT_LITERAL;

    $sql[DASH_BEGIN]["-"]      = LINE_COMMENT;
    $sql[DASH_BEGIN][0]        = NORMAL_TEXT;

    $sql[LINE_COMMENT]["\n"]   = NORMAL_TEXT;
    $sql[LINE_COMMENT]["\\"]   = LC_ESCAPE;
    $sql[LINE_COMMENT][0]      = LINE_COMMENT;

    $sql[LC_ESCAPE]["\r"]      = LC_ESCAPE;
    $sql[LC_ESCAPE][0]         = LINE_COMMENT;

    $xml[NORMAL_TEXT]["<"]     = XML_TAG_BEGIN;
    $xml[NORMAL_TEXT]["&"]     = HTML_ENTITY;
    $xml[NORMAL_TEXT][0]       = NORMAL_TEXT;
    $xml[HTML_ENTITY][";"]     = NORMAL_TEXT;
    $xml[HTML_ENTITY]["<"]     = XML_TAG_BEGIN;
    $xml[HTML_ENTITY][0]       = HTML_ENTITY;
    $xml[XML_TAG_BEGIN]["?"]   = XML_PI;
    $xml[XML_TAG_BEGIN]["!"]   = LINE_COMMENT;
    $xml[XML_TAG_BEGIN][0]     = XML_TAG;
    $xml[XML_TAG][">"]         = NORMAL_TEXT;
    $xml[XML_TAG]["\""]        = DQ_LITERAL;
    $xml[XML_TAG]["'"]         = SQ_LITERAL;
    $xml[XML_TAG][0]           = XML_TAG;
    $xml[XML_PI][">"]          = NORMAL_TEXT;
    $xml[XML_PI][0]            = XML_TAG;
    $xml[LINE_COMMENT][">"]    = NORMAL_TEXT;
    $xml[LINE_COMMENT][0]      = LINE_COMMENT;
    $xml[DQ_LITERAL]["\""]     = XML_TAG;
    $xml[DQ_LITERAL]["&"]      = DQ_ESCAPE;
    $xml[DQ_LITERAL][0]        = DQ_LITERAL;
    $xml[SQ_LITERAL]["'"]      = XML_TAG;
    $xml[SQ_LITERAL]["&"]      = SQ_ESCAPE;
    $xml[SQ_LITERAL][0]        = SQ_LITERAL;
    $xml[DQ_ESCAPE][";"]       = DQ_LITERAL;
    $xml[DQ_ESCAPE][0]         = DQ_ESCAPE;

    //
    // Main state transition table
    //
    $states = array(
        "C89"   => $c89,
        "C" => $c99,
        "C++" => $cpp,
        "PHP" => $php,
        "Perl" => $perl,
        "Java" => $java,
        "VB" => $vb,
        "C#" => $cs,
        "Ruby" => $ruby,
        "Python" => $python,
        "Pascal" => $pascal,
        "mIRC" => $mirc,
        "PL/I" => $pli,
        "SQL"  => $sql,
        "XML"  => $xml,
        "Scheme" => $sch
    );


    //
    // Process functions
    //
    $process["C89"][NORMAL_TEXT][SQ_LITERAL] = "rtrim1";
    $process["C89"][NORMAL_TEXT][DQ_LITERAL] = "rtrim1";
    $process["C89"][NORMAL_TEXT][SLASH_BEGIN] = "rtrim1";
    $process["C89"][NORMAL_TEXT][0] = "syn_proc";

    $process["C89"][SLASH_BEGIN][STAR_COMMENT] = "rtrim1";
    $process["C89"][SLASH_BEGIN][0] = "slash_putback";

    $process["Scheme"][SCH_NORMAL][SCH_STRLIT] = "sch_rtrim1";
    $process["Scheme"][SCH_NORMAL][SCH_CHRLIT] = "sch_rtrim1";
    $process["Scheme"][SCH_NORMAL][SCH_NUMLIT] = "sch_rtrim1";

    $process["SQL"][NORMAL_TEXT][SQ_LITERAL] = "rtrim1";
    $process["SQL"][NORMAL_TEXT][DQ_LITERAL] = "rtrim1";
    $process["SQL"][NORMAL_TEXT][BT_LITERAL] = "rtrim1";
    $process["SQL"][NORMAL_TEXT][DASH_BEGIN] = "rtrim1";
    $process["SQL"][NORMAL_TEXT][0] = "syn_proc";

    $process["SQL"][DASH_BEGIN][LINE_COMMENT] = "rtrim1";
    $process["SQL"][DASH_BEGIN][0] = "dash_putback";

    $process["PL/I"] = $process["C89"];

    $process["C++"] = $process["C89"];
    $process["C++"][SLASH_BEGIN][LINE_COMMENT] = "rtrim1";

    $process["C"] = $process["C++"];

    $process["PHP"] = $process["C++"];
    $process["PHP"][NORMAL_TEXT][LINE_COMMENT] = "rtrim1";

    $process["Perl"][NORMAL_TEXT][SQ_LITERAL] = "rtrim1";
    $process["Perl"][NORMAL_TEXT][DQ_LITERAL] = "rtrim1";
    $process["Perl"][NORMAL_TEXT][LINE_COMMENT] = "rtrim1";
    $process["Perl"][NORMAL_TEXT][0] = "syn_proc";

    $process["Ruby"] = $process["Perl"];
    $process["Python"] = $process["Perl"];

    $process["mIRC"][NORMAL_TEXT][DQ_LITERAL] = "rtrim1";
    $process["mIRC"][NORMAL_TEXT][LINE_COMMENT] = "rtrim1";
    $process["mIRC"][NORMAL_TEXT][0] = "syn_proc";

    $process["VB"] = $process["Perl"];

    $process["Java"] = $process["C++"];

    $process["C#"] = $process["Java"];

    $process["Pascal"] = $process["C++"];
    $process["Pascal"][NORMAL_TEXT][LINE_COMMENT] = "rtrim1";
    $process["Pascal"][NORMAL_TEXT][BLOCK_COMMENT] = "rtrim1";
    $process["Pascal"][NORMAL_TEXT][PAREN_BEGIN] = "rtrim1";
    $process["Pascal"][SLASH_BEGIN][SQ_LITERAL] = "slash_putback_rtrim1";
    $process["Pascal"][SLASH_BEGIN][DQ_LITERAL] = "slash_putback_rtrim1";
    $process["Pascal"][SLASH_BEGIN][0] = "slash_putback";
    $process["Pascal"][PAREN_BEGIN][SQ_LITERAL] = "lparen_putback_rtrim1";
    $process["Pascal"][PAREN_BEGIN][DQ_LITERAL] = "lparen_putback_rtrim1";
    $process["Pascal"][PAREN_BEGIN][STAR_COMMENT] = "rtrim1";
    $process["Pascal"][PAREN_BEGIN][0] = "lparen_putback";

    $process["XML"][NORMAL_TEXT][XML_TAG_BEGIN] = "rtrim1";
    $process["XML"][NORMAL_TEXT][HTML_ENTITY] = "rtrim1";
    $process["XML"][HTML_ENTITY][XML_TAG_BEGIN] = "rtrim1";
    $process["XML"][HTML_ENTITY][0] = "proc_void";
    $process["XML"][XML_TAG_BEGIN][XML_TAG] = "prepend_xml_opentag";
    $process["XML"][XML_TAG_BEGIN][XML_PI] = "rtrim1";
    $process["XML"][XML_TAG_BEGIN][LINE_COMMENT] = "rtrim1";
    $process["XML"][LINE_COMMENT][NORMAL_TEXT] = "rtrim1_htmlesc";
    $process["XML"][XML_TAG][NORMAL_TEXT] = "rtrim1";
    $process["XML"][XML_TAG][DQ_LITERAL] = "rtrim1";
    $process["XML"][DQ_LITERAL][XML_TAG] = "rtrim1";
    $process["XML"][DQ_LITERAL][DQ_ESCAPE] = "rtrim1";

    $process_end["C89"] = "syntax_highlight_helper";
    $process_end["C++"] = $process_end["C89"];
    $process_end["C"] = $process_end["C89"];
    $process_end["PHP"] = $process_end["C89"];
    $process_end["Perl"] = $process_end["C89"];
    $process_end["Java"] = $process_end["C89"];
    $process_end["VB"] = $process_end["C89"];
    $process_end["C#"] = $process_end["C89"];
    $process_end["Ruby"] = $process_end["C89"];
    $process_end["Python"] = $process_end["C89"];
    $process_end["Pascal"] = $process_end["C89"];
    $process_end["mIRC"] = $process_end["C89"];
    $process_end["PL/I"] = $process_end["C89"];
    $process_end["SQL"] = $process_end["C89"];
    $process_end["Scheme"] = "sch_syntax_helper";


    $edges["C89"][NORMAL_TEXT . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["C89"][NORMAL_TEXT . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["C89"][SLASH_BEGIN . "," . STAR_COMMENT] = '<span class="comment">/*';
    $edges["C89"][DQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["C89"][SQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["C89"][STAR_END . "," . NORMAL_TEXT]     = '</span>';

    $edges["Scheme"][SCH_NORMAL . "," . SCH_STRLIT] = '<span class="sch_str">"';
    $edges["Scheme"][SCH_NORMAL . "," . SCH_NUMLIT] = '<span class="sch_num">';
    $edges["Scheme"][SCH_NORMAL . "," . SCH_CHRLIT] = '<span class="sch_chr">#';
    $edges["Scheme"][SCH_STRLIT . "," . SCH_NORMAL] = '</span>';
    $edges["Scheme"][SCH_NUMLIT . "," . SCH_NORMAL] = '</span>';
    $edges["Scheme"][SCH_CHRLIT . "," . SCH_NORMAL] = '</span>';

    $edges["SQL"][NORMAL_TEXT . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["SQL"][NORMAL_TEXT . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["SQL"][DASH_BEGIN . "," . LINE_COMMENT] = '<span class="comment">--';
    $edges["SQL"][NORMAL_TEXT . "," . BT_LITERAL]   = '`';
    $edges["SQL"][DQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["SQL"][SQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["SQL"][LINE_COMMENT . "," . NORMAL_TEXT] = '</span>';

    $edges["PL/I"] = $edges["C89"];

    $edges["C++"] = $edges["C89"];
    $edges["C++"][SLASH_BEGIN . "," . LINE_COMMENT] = '<span class="comment">//';
    $edges["C++"][LINE_COMMENT . "," . NORMAL_TEXT] = '</span>';

    $edges["C"] = $edges["C++"];

    $edges["PHP"] = $edges["C++"];
    $edges["PHP"][NORMAL_TEXT . "," . LINE_COMMENT] = '<span class="comment">#';

    $edges["Perl"][NORMAL_TEXT . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["Perl"][NORMAL_TEXT . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["Perl"][DQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["Perl"][SQ_LITERAL . "," . NORMAL_TEXT]   = '</span>';
    $edges["Perl"][NORMAL_TEXT . "," . LINE_COMMENT] = '<span class="comment">#';
    $edges["Perl"][LINE_COMMENT . "," . NORMAL_TEXT] = '</span>';

    $edges["Ruby"] = $edges["Perl"];

    $edges["Python"] = $edges["Perl"];

    $edges["mIRC"][NORMAL_TEXT . "," . DQ_LITERAL] = '<span class="literal">"';
    $edges["mIRC"][NORMAL_TEXT . "," . LINE_COMMENT] = '<span class="comment">;';
    $edges["mIRC"][DQ_LITERAL . "," . NORMAL_TEXT] = '</span>';
    $edges["mIRC"][LINE_COMMENT . "," . NORMAL_TEXT] = '</span>';

    $edges["VB"] = $edges["Perl"];
    $edges["VB"][NORMAL_TEXT . "," . LINE_COMMENT] = '<span class="comment">\'';

    $edges["Java"] = $edges["C++"];

    $edges["C#"] = $edges["Java"];

    $edges["Pascal"] = $edges["C89"];
    $edges["Pascal"][PAREN_BEGIN . "," . STAR_COMMENT] = '<span class="comment">(*';
    $edges["Pascal"][PAREN_BEGIN . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["Pascal"][PAREN_BEGIN . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["Pascal"][SLASH_BEGIN . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["Pascal"][SLASH_BEGIN . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["Pascal"][SLASH_BEGIN . "," . LINE_COMMENT] = '<span class="comment">//';
    $edges["Pascal"][NORMAL_TEXT . "," . BLOCK_COMMENT] = '<span class="comment">{';
    $edges["Pascal"][LINE_COMMENT . "," . NORMAL_TEXT] = '</span>';
    $edges["Pascal"][BLOCK_COMMENT . "," . NORMAL_TEXT] = '</span>';

    $edges["XML"][NORMAL_TEXT . "," . HTML_ENTITY] = '<span class="HTML_ENTITY">&amp;';
    $edges["XML"][HTML_ENTITY . "," . NORMAL_TEXT] = '</span>';
    $edges["XML"][HTML_ENTITY . "," . XML_TAG_BEGIN] = '</span>';
    $edges["XML"][XML_TAG . "," . NORMAL_TEXT] = '&gt;</span>';
    $edges["XML"][XML_TAG_BEGIN . "," . XML_PI] = '<span class="XML_PI">&lt;?';
    $edges["XML"][XML_TAG_BEGIN . "," . LINE_COMMENT] = '<span class="comment">&lt;!';
    $edges["XML"][LINE_COMMENT . "," . NORMAL_TEXT] = '&gt;</span>';
    $edges["XML"][XML_TAG . "," . DQ_LITERAL]   = '<span class="literal">"';
    $edges["XML"][DQ_LITERAL . "," . XML_TAG] = '"</span>';
    $edges["XML"][DQ_LITERAL . "," . DQ_ESCAPE] = '<span class="HTML_ENTITY">&amp;';
    $edges["XML"][DQ_ESCAPE . "," . DQ_LITERAL] = '</span>';
    $edges["XML"][XML_TAG . "," . SQ_LITERAL]   = '<span class="literal">\'';
    $edges["XML"][SQ_LITERAL . "," . XML_TAG] = '\'</span>';
    $edges["XML"][SQ_LITERAL . "," . SQ_ESCAPE] = '<span class="HTML_ENTITY">&amp;';
    $edges["XML"][SQ_ESCAPE . "," . SQ_LITERAL] = '</span>';

    //
    // The State Machine
    //
    if (array_key_exists($language, $initial_state)) {
        $state = $initial_state[$language];
    } else {
        $state = NORMAL_TEXT;
    }
    $output = "";
    $span = "";
    while (strlen($text) > 0) {
        $ch = substr($text, 0, 1);
        $text = substr($text, 1);

        $oldstate = $state;
        $state = (array_key_exists($ch, $states[$language][$state])) ?
            $states[$language][$state][$ch] :
            $states[$language][$state][0];

        $span .= $ch;

        if ($oldstate != $state) {
            if (
                array_key_exists($language, $process) &&
                array_key_exists($oldstate, $process[$language])
            ) {
                if (array_key_exists($state, $process[$language][$oldstate])) {
                    $pf = $process[$language][$oldstate][$state];
                    $output .= $pf($span, $language, $ch);
                } else {
                    $pf = $process[$language][$oldstate][0];
                    $output .= $pf($span, $language, $ch);
                }
            } else {
                $output .= $span;
            }

            if (
                array_key_exists($language, $edges) &&
                array_key_exists("$oldstate,$state", $edges[$language])
            ) {
                $output .= $edges[$language]["$oldstate,$state"];
            }

            $span = "";
        }
    }

    if (array_key_exists($language, $process_end) && $state == NORMAL_TEXT) {
        $output .= $process_end[$language]($span, $language);
    } else {
        $output .= $span;
    }

    if ($state != NORMAL_TEXT) {
        if (
            array_key_exists($language, $edges) &&
            array_key_exists("$state," . NORMAL_TEXT, $edges[$language])
        ) {
            $output .= $edges[$language]["$state," . NORMAL_TEXT];
        }
    }

    return $output;
}
