module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({

		// Setting folder templates
		dirs: {
			css:    'assets/css',
			fonts:  'assets/fonts',
			images: 'assets/images',
			js:     'assets/js'
		},

        // Minify .js files.
		uglify: {
			options: {
				preserveComments: 'some'
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/frontend/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/frontend/',
					ext: '.min.js'
				}]
			}
		},

		// Minify all .css files.
		cssmin: {
			admin: {
				expand: true,
				cwd: '<%= dirs.css %>/admin/',
				src: [
					'*.css',
					'!*.min.css'
				],
				dest: '<%= dirs.css %>/admin/',
				ext: '.min.css'
			},
			frontend: {
				expand: true,
				cwd: '<%= dirs.css %>/frontend/',
				src: [
					'*.css',
					'!*.min.css'
				],
				dest: '<%= dirs.css %>/frontend/',
				ext: '.min.css'
			}
		},

        sass: {
            admin: {
                expand: true,
                cwd: '<%= dirs.css %>/admin/',
                src: [
                    '*.scss'
                ],
                dest: '<%= dirs.css %>/admin/',
                ext: '.css',
                options: {
                	sourcemap: 'none'
                }
            },
            frontend: {
                expand: true,
                cwd: '<%= dirs.css %>/frontend/',
                src: [
                    '*.scss'
                ],
                dest: '<%= dirs.css %>/frontend/',
                ext: '.css',
                options: {
                	sourcemap: 'none'
                }
            }
        },

		// Watch changes for assets
		watch: {
			sass: {
				files: [
					'<%= dirs.css %>/admin/*.scss',
					'<%= dirs.css %>/frontend/*.scss'
				],
				tasks: ['sass']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['uglify']
			}
		},

		makepot: {
			dist: {
				options: {
					type: 'wp-plugin',
					potHeaders: {
						'report-msgid-bugs-to': 'http://support.woothemes.com/hc/',
						'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
					}
				}
			}
		},

		checktextdomain: {
			options:{
				text_domain: 'subscribe-for-content',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php', // Include all files
					'!node_modules/**' // Exclude node_modules/
				],
				expand: true
			}
		},

        notify_hooks: {
            options: {
                enabled: true,
                max_jshint_notifications: 5, // maximum number of notifications from jshint output
                title: "Subscribe For Content", // defaults to the name in package.json, or will use project directory's name
                success: true, // whether successful grunt executions should be notified automatically
                duration: 3 // the duration of notification in seconds, for `notify-send only
            }
        }
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
    grunt.loadNpmTasks( 'grunt-notify' );

	// Register tasks
	grunt.registerTask( 'default', [
		'cssmin',
		'uglify',
        'sass',
	]);

    // Run notify
    grunt.task.run('notify_hooks');
};
