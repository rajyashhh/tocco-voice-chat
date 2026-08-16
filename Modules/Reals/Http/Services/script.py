import os
import sys
import uuid
import logging
from moviepy.video.io.ffmpeg_tools import ffmpeg_extract_subclip
from moviepy.editor import VideoFileClip

if len(sys.argv) < 4:
    print("Usage: python python_script.py <video_path> <output_path> <gif_name>")
    sys.exit(1)

video_path = sys.argv[1]

# Input video file path
input_video_path = video_path

unique_id = uuid.uuid4()

# Output subvideo file path with .mp4 extension
output_subvideo_path = sys.argv[2]
output_gif_path = sys.argv[3]
start_time = 1
end_time = 2

new_width = 162
new_height = 226


ffmpeg_extract_subclip(video_path, start_time, end_time, targetname=output_subvideo_path)
# try:?
subclip = VideoFileClip(output_subvideo_path)
resized_subclip = subclip.resize((new_width, new_height))
#
# resized_subclip.write_videofile(output_subvideo_path, codec='libx264', audio=False, preset='medium')
# except Exception as e:
#     print("Error:", str(e))
# Restore stderr


# subvideo_clip = VideoFileClip(output_subvideo_path)
# # try:
# # Set optimization parameters (e.g., reduce bitrate)
# optimized_subvideo = subvideo_clip.set_duration(end_time - start_time)
# resized_subclip.write_videofile(output_subvideo_path, codec='libx264', preset='medium')
# except Exception as e:
#     print("Error:", str(e))
frame_rate = 30  # Adjust the frame rate as needed

# subvideo_clip = VideoFileClip(output_subvideo_path)
resized_subclip.write_gif(output_gif_path, fps=frame_rate)
# import sys
# import cv2
# import uuid
# import imageio
# import numpy as np
#
# if len(sys.argv) < 2:
#     print("Usage: python python_script.py <video_path>")
#     sys.exit(1)
#
# video_path = sys.argv[1]
#
# # Input video file path
# input_video_path = video_path
#
# unique_id = uuid.uuid4()
#
# # Output subvideo file path with .mp4 extension
# output_subvideo_path = sys.argv[2] + str(unique_id) + '.mp4'
#
# # Output GIF file path with .gif extension
# output_gif_path = sys.argv[2] + str(unique_id) + '.gif'
# output_gif_path_opt = sys.argv[2] + str(unique_id) + '3' + '.gif'
# # Start and end times for the subvideo (in seconds)
# start_time = 1
# end_time = 2
#
# # Resize dimensions (new width and height)
# new_width = 640
# new_height = 480
#
# # Open the video file
# cap = cv2.VideoCapture(input_video_path)
#
# # Get video properties
# frame_rate = int(cap.get(cv2.CAP_PROP_FPS))
# frame_width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
# frame_height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
#
# # Define the codec and create a VideoWriter object for the subvideo
# fourcc = cv2.VideoWriter_fourcc(*'mp4v')  # You can use other codecs like 'XVID' or 'MJPG'
# out_subvideo = cv2.VideoWriter(output_subvideo_path, fourcc, frame_rate, (new_width, new_height), True)
#
# # Calculate frame indices for the start and end times
# start_frame = int(start_time * frame_rate)
# end_frame = int(end_time * frame_rate)
#
# # Set the current frame position to the start frame
# cap.set(cv2.CAP_PROP_POS_FRAMES, start_frame)
#
# percent=75
# # Read frames and write to the output subvideo
# while start_frame <= end_frame:
#     ret, frame = cap.read()
#     if not ret:
#         break
#     width = new_width
#     height = new_height
#     b = cv2.resize(frame,(width,height),fx=0,fy=0, interpolation = cv2.INTER_AREA)
#     out_subvideo.write(b)
#     start_frame += 1
#
# # Release video objects
# cap.release()
# out_subvideo.release()
#
# # Read the subvideo frames and convert them to a GIF
# subvideo_frames = []
# cap_subvideo = cv2.VideoCapture(output_subvideo_path)
#
# while True:
#     ret, frame = cap_subvideo.read()
#     if not ret:
#         break
#     subvideo_frames.append(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB))
#
#
# def optimize_gif(input_gif_path, output_gif_path):
#     # Read the GIF
#     gif = imageio.get_reader(input_gif_path)
#
#     # Create an array of frames
#     frames = [frame for frame in gif]
#
#     # Save the optimized GIF
#     imageio.mimsave(output_gif_path, frames, duration=gif.get_meta_data()['duration'], palettesize=256)
#
# # Write the GIF
# imageio.mimsave(output_gif_path, subvideo_frames, 'GIF', duration=1000 *( 1 / frame_rate))
#
#
#
# print(output_gif_path, end=',')
