var fs = require('node-fs-extra');
var path = require('path');

module.exports = function(grunt) {
    var appRoot = path.resolve(__dirname + '/../../..');

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
//        uglify: {
//            options: {
//                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
//            },
//            build: {
//                src: 'less/<%= pkg.name %>.js',
//                dest: 'css/<%= pkg.name %>.min.js'
//            }
//        },
//        less: {
//            development: {
//                options: {
//                    paths: ['.'],
//                    compress: false,
//                    banner: "// DO NOT MODIFY - This file is generated automatically. Use \"grunt less\" to update it.\n\n"
//                },
//                files: {
//                    'css/app.css': 'less/app.less'
//                }
//            },
//            production: {
//                //TODO
//            }
//        },
        sass: {
            dist: {
                options: {
                    style: 'expanded'
//                    loadPath: ['bower_components/foundation/scss']
                },
                files: {
                    'css/app.css': 'scss/app.scss'
                }
            }
        },
//        usebanner: {
//            less: {
//                options: {
//                    position: 'top',
//                    banner: '// banner text <%= templates encouraged %>',
//                    linebreak: true
//                },
//                files: {
//                    src: [ 'path/to/file.ext', 'path/to/another/*.ext' ]
//                }
//            }
//        },
        whbModernTheme: {
            appRoot: appRoot,
            themeRoot: __dirname,
            foundationDir: __dirname + '/node_modules/foundation-sites',
            copyFoundationDirectories: {
                'js':      'js'/*,
                'scss':    'scss'*/
            }
        }
    });

    // Load the plugin that provides the "uglify" task.
    //grunt.loadNpmTasks('grunt-contrib-uglify');

//    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-sass');
//    grunt.loadNpmTasks('grunt-banner');

    grunt.registerTask('copy_foundation', '', function () {
        var bsDirs = grunt.config.get('whbModernTheme').copyFoundationDirectories;
        for (var sourceDir in bsDirs) {
            var destDir = path.resolve(__dirname) + '/' + bsDirs[sourceDir];
            sourceDir = grunt.config.get('whbModernTheme').foundationDir + '/' + sourceDir;
            console.log(sourceDir + ' => ' + destDir);
            fs.copySync(sourceDir, destDir, function (err) {
                if (err) {
                    console.error(err);
                } else {
                    console.log("success!");
                }
            });
        }
    });

    // Default task(s).
    //grunt.registerTask('default', ['copy_foundation']);

    //grunt.registerTask('less', ['less']);

};