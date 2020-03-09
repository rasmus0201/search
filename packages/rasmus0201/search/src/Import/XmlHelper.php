<?php

namespace Search\Import;

class XmlHelper
{
    public static function hasTag($string, $tag)
    {
        $pattern = '/(<'.$tag.'(?:.*?)\/>)|(<'.$tag.'(?:.*?)>(?:.*?)<'.$tag.'\/>)/';
        preg_match($pattern, $string, $tagMatches);

        return count($tagMatches) > 0;
    }

    public static function hasTags($string, array $tags)
    {
        foreach ($tags as $tag) {
            if (XmlHelper::hasTag($string, $tag)) {
                return true;
            }
        }

        return false;
    }

    public static function extractInnerContent($string, $tag)
    {
        $pattern = '/<'.$tag.'(?:.*?)>(.*?)<\/'.$tag.'>/';
        preg_match($pattern, $string, $rawContent);

        if (!isset($rawContent[1])) {
            return '';
        }

        return $rawContent[1];
    }

    public static function extractInnerTagsByTag($string, $tag)
    {
        if (!XmlHelper::hasTag($string, $tag)) {
            return [];
        }

        $pattern = '/<'.$tag.'(?:.*?)>(.*?)<\/'.$tag.'>/';
        return preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
    }

    public static function extractAttributes($string, $tag)
    {
        if (!XmlHelper::hasTag($string, $tag)) {
            return [];
        }

        $pattern = '/<'.$tag.' (.*?)\/>/';
        preg_match($pattern, $string, $rawIdAttributes);

        if (!isset($rawIdAttributes[1])) {
            return [];
        }

        $pattern = '/("[^"]*")|\h+/';
        $unformattedAttr = preg_split($pattern, $rawIdAttributes[1], -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $attributes = [];
        for ($i = 0; $i < count($unformattedAttr); $i += 2) {
            $attributeName = mb_substr($unformattedAttr[$i], 0, -1);
            $attributeValue = mb_substr($unformattedAttr[$i + 1], 1, -1);

            $attributes[$attributeName] = $attributeValue;
        }

        return $attributes;
    }
}
