<?php
//
// Definition of eZSession class
//
// Created on: <19-Aug-2002 12:49:18 bf>
//
// Copyright (C) 1999-2004 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/products/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*!
  Re-implementation of PHP session management using database.
*/

function eZSessionOpen( )
{
    // do nothing eZDB will open connection when needed.
}

function eZSessionClose( )
{
    // eZDB will handle closing the database
}

function &eZSessionRead( $key )
{
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();

    $key =& $db->escapeString( $key );

    $sessionRes =& $db->arrayQuery( "SELECT data, user_id FROM ezsession WHERE session_key='$key'" );

    if ( count( $sessionRes ) == 1 )
    {
        $data =& $sessionRes[0]['data'];
        $GLOBALS['eZSessionUserID'] = $sessionRes[0]['user_id'];

        return $data;
    }
    else
    {
        return false;
    }
}

/*!
  Will write the session information to database.
*/
function eZSessionWrite( $key, $value )
{
//    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    $ini =& eZIni::instance();
    $expirationTime = time() + $ini->variable( 'Session', 'SessionTimeout' );

    $value =& $db->escapeString( $value );
    $key =& $db->escapeString( $key );
    // check if session already exists

    $userID = 0;
    if ( isset( $GLOBALS['eZSessionUserID'] ) )
        $userID = $GLOBALS['eZSessionUserID'];
    $userID = $db->escapeString( $userID );

    $sessionRes =& $db->arrayQuery( "SELECT session_key FROM ezsession WHERE session_key='$key'" );

    if ( count( $sessionRes ) == 1 )
    {
        $updateQuery = "UPDATE ezsession
                    SET expiration_time='$expirationTime', data='$value', user_id='$userID'
                    WHERE session_key='$key'";

        $ret = $db->query( $updateQuery );
    }
    else
    {
        $insertQuery = "INSERT INTO ezsession
                    ( session_key, expiration_time, data, user_id )
                    VALUES ( '$key', '$expirationTime', '$value', '$userID' )";

        $ret = $db->query( $insertQuery );
    }
}

/*!
  Will remove a session from the database.
*/
function eZSessionDestroy( $key )
{
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();

    $key =& $db->escapeString( $key );
    $query = "DELETE FROM ezsession WHERE session_key='$key'";

    $db->query( $query );

}

/*!
  Handles session cleanup. Will delete timed out sessions from the database.
*/
function eZSessionGarbageCollector()
{
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    $time = time();

    $query = "DELETE FROM ezsession WHERE expiration_time < " . $time;

    $db->query( $query );
}

/*!
  Removes all entries from session.
*/
function eZSessionEmpty()
{
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();

    $query = "TRUNCATE TABLE ezsession";

    $db->query( $query );

}

/*!
  get all sessions by limit and offset, and returns it
*/
function &eZSessionGetActive( $params = array() )
{
    if ( isSet( $params['limit'] ) )
        $limit = $params['limit'];
    else
        $limit = 20;

    if ( isSet( $params['offset'] ) )
        $offset = $params['offset'];
    else
        $offset = 0;
    $orderBy = "ezsession.expiration_time DESC";

    switch ( $params['sortby'] )
    {
        case 'login':
        {
            $orderBy = "ezuser.login ASC";
        }break;

        case 'email':
        {
            $orderBy = "ezuser.email ASC";
        }break;

        case 'name':
        {
            $orderBy = "ezcontentobject.name ASC";
        }break;

        case 'idle':
        {
            $orderBy = "ezsession.expiration_time DESC";
        }break;
    }
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    $query = "SELECT ezsession.user_id, ezsession.expiration_time, ezsession.session_key FROM ezsession, ezuser, ezcontentobject WHERE ezsession.user_id=ezuser.contentobject_id AND ezsession.user_id=ezcontentobject.id ORDER BY $orderBy";

    $rows = $db->arrayQuery( $query, array( 'offset' => $offset, 'limit' => $limit ) );

    $time = mktime();
    $ini =& eZINI::instance();
    $activityTimeout = $ini->variable( 'Session', 'ActivityTimeout' );
    $sessionTimeout = $ini->variable( 'Session', 'SessionTimeout' );
    $sessionTimeoutValue = $time - $sessionTimeou;

    $resultArray = array();
    foreach( $rows as $row )
    {
        $sessionUser =& eZUser::fetch( $row['user_id'], true );
        $session['user_id'] = $row['user_id'];
        $session['expiration_time'] = $row['expiration_time'];
        $session['session_key'] = $row['session_key'];
        $session['idle_time'] = $row['expiration_time'] - $sessionTimeout;
        $idleTime = $time - $row['expiration_time'] + $sessionTimeout;
        $minute = abs( $time % 60 );
        $hour = (int)($time / 60);
        $session['idle']['hour'] = (int)($idleTime / 3600);
        $session['idle']['minute'] = (int)(($idleTime / 60) % 60);
        $session['idle']['second'] = abs($idleTime % 60);

        if ( $session['idle']['minute'] < 10 )
        {
            $session['idle']['minute'] = "0" . $session['idle']['minute'];
        }

        if ( $session['idle']['second'] < 10 )
        {
            $session['idle']['second'] = "0" . $session['idle']['second'];
        }

        $session['email'] = $sessionUser->attribute( 'email' );
        $session['login'] = $sessionUser->attribute( 'login' );
        $resultArray[] = $session;
    }
    return $resultArray;
}

/*!
  Counts the number of active session and returns it.
*/
function eZSessionCountActive()
{
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    $query = "SELECT count( * ) AS count FROM ezsession";

    $rows = $db->arrayQuery( $query );
    return $rows[0]['count'];
}

/*!
 Register the needed session functions.
 Call this only once.
*/
function eZRegisterSessionFunctions()
{
    session_module_name( 'user' );
    $ini =& eZIni::instance();
    if ( $ini->variable( 'Session', 'SessionNameHandler' ) == 'custom' )
    {
        $sessionName = $ini->variable( 'Session', 'SessionNamePrefix' );
        if ( $ini->variable( 'Session', 'SessionNamePerSiteAccess' ) == 'enabled' )
        {
            $access = $GLOBALS['eZCurrentAccess'];
            $sessionName .=  $access['name'];
        }
        session_name( $sessionName );
    }
    session_set_save_handler(
        'ezsessionopen',
        'ezsessionclose',
        'ezsessionread',
        'ezsessionwrite',
        'ezsessiondestroy',
        'ezsessiongarbagecollector' );
}

/*!
 Makes sure that the session is started properly.
 Multiple calls will just be ignored.
*/
function eZSessionStart()
{
    // Check if we are allowed to use sessions
    if ( isset( $GLOBALS['eZSiteBasics'] ) and
         isset( $GLOBALS['eZSiteBasics']['session-required'] ) and
         !$GLOBALS['eZSiteBasics']['session-required'] )
        return false;
    $hasStarted =& $GLOBALS['eZSessionIsStarted'];
    if ( isset( $hasStarted ) and
         $hasStarted )
         return false;
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    if ( !$db->isConnected() )
        return false;
    eZRegisterSessionFunctions();
    $ini =& eZINI::instance();
    $cookieTimeout = $ini->variable( 'Session', 'CookieTimeout' );
    if ( is_numeric( $cookieTimeout ) )
    {
        session_set_cookie_params( (int)$cookieTimeout );
    }
    session_start();
//     eZDebug::writeDebug( "Session is started" );
    $hasStarted = true;
    return true;
}

/*!
 Makes sure session data is stored in the session and stops the session.
*/
function eZSessionStop()
{
    $hasStarted =& $GLOBALS['eZSessionIsStarted'];
    if ( isset( $hasStarted ) and
         !$hasStarted )
         return false;
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    if ( !$db->isConnected() )
        return false;
    session_write_close();
    $hasStarted = false;
    return true;
}

/*!
 Will make sure the user gets a new session ID while keepin the session data.
 This is useful to call on logins, to avoid sessions theft from users.
 \note This requires PHP 4.3.2 and higher which has the session_regenerate_id
 \return \c true if succesful
*/
function eZSessionRegenerate()
{
    $hasStarted =& $GLOBALS['eZSessionIsStarted'];
    if ( isset( $hasStarted ) and
         !$hasStarted )
         return false;
    if ( !function_exists( 'session_regenerate_id' ) )
        return false;
    // This doesn't seem to work as expected
//     session_regenerate_id();
    return true;
}

/*!
 Removes the current session and resets session variables.
*/
function eZSessionRemove()
{
    $hasStarted =& $GLOBALS['eZSessionIsStarted'];
    if ( isset( $hasStarted ) and
         !$hasStarted )
         return false;
    include_once( 'lib/ezdb/classes/ezdb.php' );
    $db =& eZDB::instance();
    if ( !$db->isConnected() )
        return false;
    $_SESSION = array();
    session_destroy();
    $hasStarted = false;
    return true;
}

/*!
 Sets the current user ID to \a $userID,
 this ID will be written to the session table field user_id
 when the page is done.
 \sa eZSessionUserID
*/
function eZSessionSetUserID( $userID )
{
    $GLOBALS['eZSessionUserID'] = $userID;
}

/*!
 Returns the current session ID.
 The session handler will not care about value of the ID,
 it's entirely up to the clients of the session handler to use and update this value.
*/
function eZSessionUserID()
{
    if ( isset( $GLOBALS['eZSessionUserID'] ) )
        return $GLOBALS['eZSessionUserID'];
    return 0;
}

?>
