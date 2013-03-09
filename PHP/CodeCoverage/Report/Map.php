<?php

/**
 * Generates an Array from an PHP_CodeCoverage object.
 *
 * @category    PHP
 * @package     CodeCoverage
 * @author      gbr.alonso@gmail.com <gbr.alonso@gmail.com>
 * @copyright   2013 Gabriel Alonso.
 * 
 */
class PHP_CodeCoverage_Report_Map {

    public function process(PHP_CodeCoverage $coverage, $target = NULL, $name = NULL) {

        $aCoverage = array(
            'generated' => (int) $_SERVER['REQUEST_TIME']
        );

        $aProject = array();

        $report = $coverage->getReport();
        unset($coverage);

        foreach ($report as $item) {
            $namespace = 'global';

            if (!$item instanceof PHP_CodeCoverage_Report_Node_File) {
                continue;
            }

            $aFile[$item->getPath()] = array(
                'file' => $item->getPath()
            );

            $classes = $item->getClassesAndTraits();
            $coverage = $item->getCoverageData();
            $lines = array();
            $ignoredLines = $item->getIgnoredLines();

            foreach ($classes as $className => $class) {
                $classStatements = 0;
                $coveredClassStatements = 0;
                $coveredMethods = 0;

                foreach ($class['methods'] as $methodName => $method) {
                    $methodCount = 0;
                    $methodLines = 0;
                    $methodLinesCovered = 0;

                    for ($i = $method['startLine']; $i <= $method['endLine']; $i++) {
                        if (isset($ignoredLines[$i])) {
                            continue;
                        }

                        $add = TRUE;
                        $count = 0;

                        if (isset($coverage[$i])) {
                            if ($coverage[$i] !== NULL) {
                                $classStatements++;
                                $methodLines++;
                            } else {
                                $add = FALSE;
                            }

                            $count = count($coverage[$i]);

                            if ($count > 0) {
                                $coveredClassStatements++;
                                $methodLinesCovered++;
                            }
                        } else {
                            $add = FALSE;
                        }

                        $methodCount = max($methodCount, $count);

                        if ($add) {
                            $lines[$i] = array(
                                'count' => $count, 'type' => 'stmt'
                            );
                        }
                    }

                    if ($methodCount > 0) {
                        $coveredMethods++;
                    }

                    $lines[$method['startLine']] = array(
                        'count' => $methodCount,
                        'crap' => $method['crap'],
                        'type' => 'method',
                        'name' => $methodName
                    );
                }

                if (!empty($class['package']['namespace'])) {
                    $namespace = $class['package']['namespace'];
                }

                $aClass[$className] = array(
                    'name' => $className,
                    'namespace' => $namespace
                );

                if (!empty($class['package']['fullPackage'])) {
                    $aClass[$className]['fullPackage'] = $class['package']['fullPackage'];
                }

                if (!empty($class['package']['category'])) {
                    $aClass[$className]['category'] = $class['package']['category'];
                }

                if (!empty($class['package']['package'])) {
                    $aClass[$className]['package'] = $class['package']['package'];
                }

                if (!empty($class['package']['subpackage'])) {
                    $aClass[$className]['subpackage'] = $class['package']['subpackage'];
                }

                $aMetrics = array(
                    'methods' => count($class['methods']),
                    'coveredmethods' => $coveredMethods,
                    'conditionals' => 0,
                    'coveredconditionals' => 0,
                    'statements' => $classStatements,
                    'coveredstatements' => $coveredClassStatements,
                    'elements' => count($class['methods']) + $classStatements,
                    'coveredelements' => $coveredMethods + $coveredClassStatements
                );

                $aClass[$className]['metrics'] = $aMetrics;

                $aFile[$item->getPath()] = array(
                    'class' => $aClass
                );

                $aClass = array();
            }

            foreach ($coverage as $line => $data) {
                if ($data === NULL ||
                        isset($lines[$line]) ||
                        isset($ignoredLines[$line])) {
                    continue;
                }

                $lines[$line] = array(
                    'count' => count($data), 'type' => 'stmt'
                );
            }

            ksort($lines);

            foreach ($lines as $line => $data) {
                if (isset($ignoredLines[$line])) {
                    continue;
                }

                $aLines[$line] = array(
                    'num' => $line,
                    'type' => $data['type'],
                );

                if (isset($data['name'])) {
                    $aLines[$line]['name'] = $data['name'];
                }

                if (isset($data['crap'])) {
                    $aLines[$line]['crap'] = $data['crap'];
                }

                $aLines[$line]['count'] = $data['count'];

                $aFile[$item->getPath()]['line'] = $aLines;
            }

            $aLines = array();

            $linesOfCode = $item->getLinesOfCode();

            $aFileMetrcis = array(
                'loc' => $linesOfCode['loc'],
                'ncloc' => $linesOfCode['ncloc'],
                'classes' => $item->getNumClassesAndTraits(),
                'methods' => $item->getNumMethods(),
                'coveredmethods' => $item->getNumTestedMethods(),
                'conditionals' => 0,
                'coveredconditionals' => 0,
                'statements' => $item->getNumExecutableLines(),
                'coveredstatements' => $item->getNumExecutedLines(),
                'elements' => $item->getNumMethods() + $item->getNumExecutableLines(),
                'coveredelements' => $item->getNumTestedMethods() + $item->getNumExecutedLines()
            );

            $aFile[$item->getPath()]['metrics'] = $aFileMetrcis;

            if ($namespace == 'global') {
                $aProject[$item->getPath()] = $aFile;
            } else {

                $aProject[$namespace][] = $aFile;
            }

            $aFile = array();
        }

        $linesOfCode = $report->getLinesOfCode();

        $aMegaMetrics = array(
            'files' => count($report),
            'loc' => $linesOfCode['loc'],
            'ncloc' => $linesOfCode['ncloc'],
            'classes' => $report->getNumClassesAndTraits(),
            'methods' => $report->getNumMethods(),
            'coveredmethods' => $report->getNumTestedMethods(),
            'conditionals' => 0,
            'coveredconditionals' => 0,
            'statements' => $report->getNumExecutableLines(),
            'coveredstatements' => $report->getNumExecutedLines(),
            'elements' => $report->getNumMethods() + $report->getNumExecutableLines(),
            'coveredelements' => $report->getNumTestedMethods() + $report->getNumExecutedLines(),
        );

        $aProject['metrics'] = $aMegaMetrics;

        $aCoverage['project'] = $aProject;

        return $aCoverage;
    }

}

