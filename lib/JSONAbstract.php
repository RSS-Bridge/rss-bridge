<?php

/**
 * An alternative abstract class for bridges utilizing JSON input
 *
 * This class is meant as an alternative base class for bridge implementations.
 * It offers preliminary functionality for generating feeds based on XPath
 * expressions.
 * As a minimum, extending classes should define XPath expressions pointing
 * to the feed items contents in the class constants below. In case there is
 * more manual fine tuning required, it offers a bunch of methods which can
 * be overridden, for example in order to specify formatting of field values
 * or more flexible definition of dynamic XPath expressions.
 *
 * This class extends {@see BridgeAbstract}, which means it incorporates and
 * extends all of its functionality.
 **/

abstract class XPathAbstract extends BridgeAbstract {
